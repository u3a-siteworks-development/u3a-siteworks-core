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
      },
      startorend: {
        type: "string",
		    default: 'start'
      },
      order: {
        type: "string",
		    default: 'desc'
      },
      maxnumber: {
        type: "integer",
		    default: 5
      },
    },
    edit: function ({attributes, setAttributes }) {
      const { title, showtitle, startorend, order, maxnumber } = attributes;

      const InspectorControls = wp.blockEditor.InspectorControls;
      const PanelBody = wp.components.PanelBody;
      const TextControl = wp.components.TextControl;
      const ToggleControl = wp.components.ToggleControl;
      const SelectControl = wp.components.SelectControl;

      const onChangeTitle = val => {
        setAttributes( { title: val });
      };
      const onChangeShowTitle = val => {
        setAttributes( { showtitle: val });
      };
      const onChangeStartorend = val => {
        setAttributes( { startorend: val });
      };
      const onChangeOrder = val => {
        setAttributes( { order: val });
      };
      const onChangeMaxnumber = val => {
        setAttributes( { maxnumber: Number(val) });
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
            ),
            wp.element.createElement( SelectControl,
            { label:'Order by Start or End', 
              value: startorend,
              onChange: onChangeStartorend,
              options:[
              {
              label: 'Start',
              value: 'start',
              },
              {
              label: 'End',
              value: 'end',
              }
              ]
            }
            ),
            wp.element.createElement( SelectControl,
            { label:'Order', 
              value: order,
              onChange: onChangeOrder,
              options:[
              {
              label: 'Ascending',
              value: 'asc',
              },
              {
              label: 'Descending',
              value: 'desc',
              }
              ]
            }
            ),
            wp.element.createElement( SelectControl,
            { label:'Max number to list', 
              value: maxnumber,
              onChange: onChangeMaxnumber,
              options:[
              {
              label: '1',
              value: 1,
              },
              {
              label: '2',
              value: 2,
              },
              {
              label: '3',
              value: 3,
              },
              {
              label: '4',
              value: 4,
              },
              {
              label: '5',
              value: 5,
              },
              {
              label: '6',
              value: 6,
              },
              {
              label: '7',
              value: 7,
              },
              {
              label: 'No limit',
              value: -1,
              },
              ]
            }
            ),
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
