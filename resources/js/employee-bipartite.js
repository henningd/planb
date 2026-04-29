import cytoscape from 'cytoscape';
import dagre from 'cytoscape-dagre';

cytoscape.use(dagre);

// Bipartiter Graph (Mitarbeiter ↔ Rollen / Mitarbeiter ↔ Systeme).
// Layout: Dagre LR (links → rechts), damit Mitarbeiter links und Ziel-Knoten
// (Rollen / Systeme) rechts erscheinen — visuell klar als Zuordnung lesbar.
const DAGRE_LAYOUT = {
    name: 'dagre',
    rankDir: 'LR',
    nodeSep: 26,
    rankSep: 130,
    edgeSep: 14,
    animate: true,
    animationDuration: 350,
    fit: true,
    padding: 30,
};

export function initEmployeeBipartite(opts) {
    const root = document.getElementById(opts.containerId);
    if (!root) return null;

    const cy = cytoscape({
        container: root,
        elements: [...opts.nodes, ...opts.edges],
        wheelSensitivity: 1.6,
        minZoom: 0.1,
        maxZoom: 4,
        style: [
            {
                selector: 'node',
                style: {
                    label: 'data(label)',
                    'background-color': '#eef2ff',
                    'border-color': '#6366f1',
                    'border-width': 2,
                    color: '#1e293b',
                    shape: 'round-rectangle',
                    width: 'label',
                    height: 'label',
                    padding: '10px',
                    'text-valign': 'center',
                    'text-halign': 'center',
                    'text-wrap': 'wrap',
                    'text-max-width': '170px',
                    'font-size': '12px',
                    'font-weight': 600,
                    'transition-property': 'opacity, border-width, background-color',
                    'transition-duration': '180ms',
                },
            },
            // Mitarbeiter mit Krisenrolle
            {
                selector: 'node[kind = "employee"][?has_crisis_role]',
                style: {
                    'background-color': '#fee2e2',
                    'border-color': '#dc2626',
                },
            },
            // Schlüsselpersonen
            {
                selector: 'node[kind = "employee"][?is_key_personnel]',
                style: {
                    'background-color': '#fef3c7',
                    'border-color': '#d97706',
                },
            },
            // Ziel-Knoten: Rollen
            {
                selector: 'node[kind = "role"]',
                style: {
                    'background-color': '#ecfeff',
                    'border-color': '#0891b2',
                    shape: 'round-tag',
                },
            },
            // System-Rolle (vom System angelegt, z. B. CrisisRole)
            {
                selector: 'node[kind = "role"][?is_system]',
                style: {
                    'background-color': '#dbeafe',
                    'border-color': '#1d4ed8',
                },
            },
            // Ziel-Knoten: Systeme
            {
                selector: 'node[kind = "system"]',
                style: {
                    'background-color': '#f0fdf4',
                    'border-color': '#16a34a',
                    shape: 'round-rectangle',
                },
            },
            // Auswahl
            {
                selector: 'node:selected',
                style: {
                    'border-width': 4,
                    'border-color': '#1d4ed8',
                },
            },
            {
                selector: 'edge',
                style: {
                    width: 2,
                    'line-color': '#9ca3af',
                    'target-arrow-color': '#9ca3af',
                    'target-arrow-shape': 'triangle',
                    // Bezier mit Multi-Edge-Stack, damit parallele Kanten
                    // (direkt + über mehrere Rollen) sichtbar nebeneinander liegen.
                    'curve-style': 'bezier',
                    'control-point-step-size': 22,
                    'arrow-scale': 1.1,
                    'transition-property': 'opacity, line-color, width',
                    'transition-duration': '180ms',
                },
            },
            // Tripartite-Kanten Mitarbeiter → Rolle → System: gepunktet,
            // türkis, schmaler — visuell als zusammenhängende Kette
            // erkennbar, klar abgegrenzt von der direkten RACI-Kante.
            {
                selector: 'edge[kind = "emp-role"], edge[kind = "role-sys"]',
                style: {
                    'line-style': 'dotted',
                    'line-color': '#0d9488',
                    'target-arrow-color': '#0d9488',
                    width: 1.5,
                },
            },
            // Stellvertretungs-Kante in den bipartiten Rollen-Graphen
            // (Mitarbeiter ↔ Rolle): gestrichelt lila.
            {
                selector: 'edge[?is_deputy]',
                style: {
                    'line-style': 'dashed',
                    'line-color': '#a855f7',
                    'target-arrow-color': '#a855f7',
                },
            },
            // Direkte Stellvertretungs-Zuweisung (Mitarbeiter → System)
            // bleibt durch das gleiche dashed/lila kenntlich.
            {
                selector: 'edge[kind = "direct"][?is_deputy]',
                style: {
                    'line-style': 'dashed',
                    'line-color': '#a855f7',
                    'target-arrow-color': '#a855f7',
                },
            },
            {
                selector: '.faded',
                style: { opacity: 0.18 },
            },
            {
                selector: '.highlight-edge',
                style: {
                    'line-color': '#0ea5e9',
                    'target-arrow-color': '#0ea5e9',
                    width: 3,
                },
            },
            {
                selector: 'node.highlight-node',
                style: {
                    'border-width': 3,
                    'border-color': '#1d4ed8',
                },
            },
            {
                selector: '.is-hidden',
                style: { display: 'none' },
            },
        ],
        layout: DAGRE_LAYOUT,
    });

    if (typeof ResizeObserver !== 'undefined') {
        const ro = new ResizeObserver(() => {
            cy.resize();
            cy.fit(undefined, 30);
        });
        ro.observe(root);
    }

    const fadeOthers = (nodes, edges) => {
        cy.elements().addClass('faded');
        nodes.removeClass('faded').addClass('highlight-node');
        edges.removeClass('faded').addClass('highlight-edge');
    };

    const clearHighlight = () => {
        cy.elements().removeClass('faded highlight-edge highlight-node');
    };

    // Auf Hover: Knoten + alle direkt angrenzenden Knoten/Kanten hervorheben.
    const highlight = (node) => {
        const neighborhood = node.neighborhood();
        const allNodes = node.union(neighborhood.filter('node'));
        const allEdges = neighborhood.filter('edge');
        fadeOthers(allNodes, allEdges);
    };

    cy.on('mouseover', 'node', (e) => highlight(e.target));
    cy.on('mouseout', 'node', () => clearHighlight());

    cy.on('tap', 'node', (e) => {
        if (typeof opts.onSelect === 'function') {
            opts.onSelect(e.target.data());
        }
    });

    cy.on('tap', (e) => {
        if (e.target === cy && typeof opts.onSelect === 'function') {
            opts.onSelect(null);
        }
    });

    return {
        cy,
        relayout() {
            cy.layout(DAGRE_LAYOUT).run();
        },
        fit() {
            cy.fit(undefined, 30);
        },
        zoomBy(factor) {
            const center = { x: cy.width() / 2, y: cy.height() / 2 };
            cy.zoom({
                level: Math.max(cy.minZoom(), Math.min(cy.maxZoom(), cy.zoom() * factor)),
                renderedPosition: center,
            });
        },
        resetZoom() {
            const center = { x: cy.width() / 2, y: cy.height() / 2 };
            cy.zoom({ level: 1, renderedPosition: center });
            cy.center();
        },
        applyFilter({ search }) {
            const haystack = (search || '').trim().toLowerCase();
            cy.batch(() => {
                cy.nodes().forEach((n) => {
                    const label = (n.data('label') || '').toLowerCase();
                    if (!haystack || label.includes(haystack)) {
                        n.removeClass('is-hidden');
                    } else {
                        n.addClass('is-hidden');
                    }
                });
                cy.edges().forEach((e) => {
                    const sv = !e.source().hasClass('is-hidden');
                    const tv = !e.target().hasClass('is-hidden');
                    if (sv && tv) {
                        e.removeClass('is-hidden');
                    } else {
                        e.addClass('is-hidden');
                    }
                });
            });
            const visible = cy.elements(':visible');
            if (visible.length > 0) {
                cy.fit(visible, 30);
            }
        },
        destroy() {
            cy.destroy();
        },
    };
}

window.PlanB = window.PlanB || {};
window.PlanB.initEmployeeBipartite = initEmployeeBipartite;
