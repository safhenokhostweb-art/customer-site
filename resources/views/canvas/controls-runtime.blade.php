<script>
window.CanvasControls = window.CanvasControls || {
    GENERIC_STYLE_SECTIONS: {
        typography: 'تایپوگرافی',
        spacing: 'فاصله‌ها',
        background: 'پس‌زمینه',
        border: 'حاشیه و کادر',
        shadow: 'سایه',
        layout: 'چیدمان'
    },
    hiddenStyleSections: function(capabilities) {
        if (!capabilities || !capabilities.hidden_style_sections) return [];
        return capabilities.hidden_style_sections;
    },
    visibleFields: function(schema, panel, condition, state) {
        if (!schema || !Array.isArray(schema)) return [];
        return schema.filter(function(field) {
            if (panel && field.panel && field.panel !== panel) return false;
            return true;
        });
    },
    editorMixin: function() {
        return {
            ctlChecked: function(propKey) {
                if (!this.selectedBlock || !this.selectedBlock.props) return false;
                return Boolean(this.selectedBlock.props[propKey]);
            },
            panelHasFields: function(tabName) {
                if (!this.selectedBlock) return false;
                var schema = this.selectedBlockSchema || [];
                return schema.some(function(field) { return field.panel === tabName; });
            },
            genericStyleVisible: function(sectionKey) {
                var caps = this.selectedEditorCapabilities || {};
                var hidden = CanvasControls.hiddenStyleSections(caps);
                return hidden.indexOf(sectionKey) === -1;
            }
        };
    }
};
</script>
