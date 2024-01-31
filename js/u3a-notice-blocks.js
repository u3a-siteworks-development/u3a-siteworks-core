let TextControl = wp.components.TextControl;
let ToggleControl = wp.components.ToggleControl;

wp.blocks.registerBlockType("u3a/noticelist", {
    title: "u3a notice list",
    description: "Displays current notices",
    icon: "testimonial",
    category: "widgets",
    attributes: {
      title: {
        type: "string",
        default: "Latest Notices"
      },
      showtitle: {
        type: "boolean",
        default: true
      }
    },
    edit: function ({attributes, setAttributes }) {
      const { title, showtitle } = attributes;
      const onChangeTitle = val => {
        setAttributes( { title: val });
      };
      const onChangeShowTitle = val => {
        setAttributes( { showtitle: val });
      };
      var nest = [
        wp.element.createElement(
          InspectorControls,
          {}, wp.element.createElement( PanelBody, {title:'Title', initialOpen:true }, 
            wp.element.createElement( TextControl,
              { label:'Title', 
                value: title,
                onChange: onChangeTitle,
              }
            ),
            wp.element.createElement( ToggleControl,
              { label:'Show Title', 
                checked: showtitle,
                onChange: onChangeShowTitle,
              }
            )
          )
        ),
        wp.element.createElement("div", {className: 'wp-block alignwide', style: {color: 'white', backgroundColor: '#005ab8', padding: '10px'}}, "This placeholder shows where the list of notices will be shown.")
      ];
      return nest;
    },
    save: function () {
      return null
    }
  })
