(function (blocks, element, blockEditor, components, apiFetch) {
    var el = element.createElement;
    var useState = element.useState;
    var useEffect = element.useEffect;
    var InspectorControls = blockEditor.InspectorControls;
    var PanelBody = components.PanelBody;
    var TextControl = components.TextControl;
    var RangeControl = components.RangeControl;
    var Spinner = components.Spinner;

    blocks.registerBlockType('afc/partner-directory', {
        title: 'AFC Partner Directory',
        icon: 'groups',
        category: 'widgets',
        attributes: {
            category: { type: 'string', default: '' },
            columns:  { type: 'number', default: 3 }
        },

        edit: function (props) {
            var attributes    = props.attributes;
            var setAttributes = props.setAttributes;
            var _state        = useState({ partners: [], loading: true, error: null });
            var state         = _state[0];
            var setState      = _state[1];

            useEffect(function () {
                setState({ partners: [], loading: true, error: null });
                var path = '/custom/v1/partners?per_page=100';
                if (attributes.category) {
                    path += '&category=' + encodeURIComponent(attributes.category);
                }
                apiFetch({ path: path })
                    .then(function (data) {
                        setState({ partners: data.partners || [], loading: false, error: null });
                    })
                    .catch(function () {
                        setState({ partners: [], loading: false, error: 'Failed to load partners.' });
                    });
            }, [attributes.category]);

            return el(
                'div', { className: 'afc-partner-directory-editor' },
                el(InspectorControls, null,
                    el(PanelBody, { title: 'Display Settings', initialOpen: true },
                        el(TextControl, {
                            label: 'Filter by Category Slug',
                            value: attributes.category,
                            onChange: function (val) { setAttributes({ category: val }); },
                            help: 'Enter a category slug to filter partners, or leave blank to show all.'
                        }),
                        el(RangeControl, {
                            label: 'Columns',
                            value: attributes.columns,
                            onChange: function (val) { setAttributes({ columns: val }); },
                            min: 1,
                            max: 6
                        })
                    )
                ),
                state.loading
                    ? el('div', { style: { padding: '20px', textAlign: 'center' } }, el(Spinner))
                    : state.error
                        ? el('p', { style: { color: 'red' } }, state.error)
                        : state.partners.length === 0
                            ? el('p', null, 'No partners found. Add some via the Partners menu.')
                            : el('div', {
                                    className: 'afc-partner-directory',
                                    style: { '--afc-columns': attributes.columns }
                                },
                                state.partners.map(function (partner) {
                                    return el(
                                        'div', { className: 'afc-partner-card', key: partner.id },
                                        partner.logo_url
                                            ? el('div', { className: 'afc-partner-logo' },
                                                el('img', { src: partner.logo_url, alt: partner.name + ' logo' })
                                              )
                                            : null,
                                        el('div', { className: 'afc-partner-name' }, partner.name)
                                    );
                                })
                              )
            );
        },

        save: function () {
            return null;
        }
    });
}(
    window.wp.blocks,
    window.wp.element,
    window.wp.blockEditor,
    window.wp.components,
    window.wp.apiFetch
));
