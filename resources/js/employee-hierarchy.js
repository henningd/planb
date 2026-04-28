import cytoscape from 'cytoscape';
import dagre from 'cytoscape-dagre';

cytoscape.use(dagre);

const DAGRE_LAYOUT = {
    name: 'dagre',
    rankDir: 'TB',
    nodeSep: 32,
    rankSep: 90,
    edgeSep: 18,
    animate: true,
    animationDuration: 350,
    fit: true,
    padding: 30,
};

export function initEmployeeHierarchy(opts) {
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
            {
                selector: 'node[?is_key_personnel]',
                style: {
                    'background-color': '#fef3c7',
                    'border-color': '#d97706',
                },
            },
            {
                selector: 'node[?has_crisis_role]',
                style: {
                    'background-color': '#fee2e2',
                    'border-color': '#dc2626',
                },
            },
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
                    'curve-style': 'bezier',
                    'arrow-scale': 1.1,
                    'transition-property': 'opacity, line-color, width',
                    'transition-duration': '180ms',
                },
            },
            {
                selector: '.faded',
                style: { opacity: 0.18 },
            },
            {
                selector: '.highlight-up',
                style: {
                    'line-color': '#0ea5e9',
                    'target-arrow-color': '#0ea5e9',
                    width: 3,
                },
            },
            {
                selector: '.highlight-down',
                style: {
                    'line-color': '#f59e0b',
                    'target-arrow-color': '#f59e0b',
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

    // Container kann beim ersten Mount (Tab-Wechsel) noch 0×0 groß sein —
    // dann legt Cytoscape den Graph in den Nullpunkt. ResizeObserver triggert
    // ein resize+fit, sobald das Layout des Containers fertig ist.
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
        edges.removeClass('faded');
    };

    const clearHighlight = () => {
        cy.elements().removeClass('faded highlight-up highlight-down highlight-node');
    };

    const highlight = (node) => {
        const upstream = node.predecessors();
        const downstream = node.successors();
        const upNodes = upstream.filter('node');
        const downNodes = downstream.filter('node');
        const upEdges = upstream.filter('edge');
        const downEdges = downstream.filter('edge');
        const allNodes = node.union(upNodes).union(downNodes);
        const allEdges = upEdges.union(downEdges);
        fadeOthers(allNodes, allEdges);
        upEdges.addClass('highlight-up');
        downEdges.addClass('highlight-down');
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
        applyFilter({ search, department }) {
            const haystack = (search || '').trim().toLowerCase();
            cy.batch(() => {
                cy.nodes().forEach((n) => {
                    const dept = n.data('department') || '';
                    const label = (n.data('label') || '').toLowerCase();
                    const deptOk = !department || dept === department;
                    const searchOk = !haystack || label.includes(haystack);
                    if (deptOk && searchOk) {
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
            // Layout neu rechnen, damit sichtbare Knoten nicht in alten
            // Positionen zwischen ausgeblendeten Lücken hängen bleiben.
            cy.layout(DAGRE_LAYOUT).run();
        },
        destroy() {
            cy.destroy();
        },
    };
}

window.PlanB = window.PlanB || {};
window.PlanB.initEmployeeHierarchy = initEmployeeHierarchy;
