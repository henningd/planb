import cytoscape from 'cytoscape';
import dagre from 'cytoscape-dagre';
import fcose from 'cytoscape-fcose';

cytoscape.use(dagre);
cytoscape.use(fcose);

const LAYOUTS = {
    dagre: {
        name: 'dagre',
        rankDir: 'LR',
        nodeSep: 40,
        rankSep: 110,
        edgeSep: 20,
        animate: true,
        animationDuration: 350,
        fit: true,
        padding: 30,
    },
    fcose: {
        name: 'fcose',
        animate: true,
        animationDuration: 450,
        nodeRepulsion: 12000,
        idealEdgeLength: 130,
        gravity: 0.1,
        fit: true,
        padding: 30,
    },
    breadthfirst: {
        name: 'breadthfirst',
        directed: true,
        spacingFactor: 1.4,
        animate: true,
        animationDuration: 350,
        fit: true,
        padding: 30,
    },
    concentric: {
        name: 'concentric',
        animate: true,
        animationDuration: 350,
        minNodeSpacing: 50,
        concentric: (node) => node.degree(),
        levelWidth: () => 1,
        fit: true,
        padding: 30,
    },
};

export function initDependencyGraph(opts) {
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
                    'background-color': 'data(level_color)',
                    'border-color': 'data(level_border)',
                    'border-width': 2,
                    color: 'data(level_text)',
                    shape: 'round-rectangle',
                    width: 'label',
                    height: 'label',
                    padding: '12px',
                    'text-valign': 'center',
                    'text-halign': 'center',
                    'text-wrap': 'wrap',
                    'text-max-width': '160px',
                    'font-size': '13px',
                    'font-weight': 600,
                    'transition-property': 'opacity, border-width, background-color',
                    'transition-duration': '180ms',
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
                style: { opacity: 0.15 },
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
        ],
        layout: LAYOUTS[opts.layout || 'dagre'],
    });

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
        relayout(name) {
            cy.layout(LAYOUTS[name] || LAYOUTS.dagre).run();
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
        applyFilter({ levels, categories, search }) {
            const haystack = (search || '').trim().toLowerCase();
            cy.batch(() => {
                cy.nodes().forEach((n) => {
                    const lvl = n.data('level');
                    const cat = n.data('category');
                    const label = (n.data('label') || '').toLowerCase();
                    const lvlOk = !levels.length || levels.includes(lvl);
                    const catOk = !categories.length || categories.includes(cat);
                    const searchOk = !haystack || label.includes(haystack);
                    n.style('display', lvlOk && catOk && searchOk ? 'element' : 'none');
                });
                cy.edges().forEach((e) => {
                    const sv = e.source().style('display') !== 'none';
                    const tv = e.target().style('display') !== 'none';
                    e.style('display', sv && tv ? 'element' : 'none');
                });
            });
        },
        destroy() {
            cy.destroy();
        },
    };
}

window.PlanB = window.PlanB || {};
window.PlanB.initDependencyGraph = initDependencyGraph;
