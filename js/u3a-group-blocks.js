
let NumberControl = wp.components.__experimentalNumberControl;
let PanelColorSettings = wp.editor.PanelColorSettings;
let useBlockProps = wp.editor.useBlockProps;
let useSelect = wp.data.useSelect;
let PanelBody = wp.components.PanelBody;
let SelectControl = wp.components.SelectControl;
let InspectorControls = wp.blockEditor.InspectorControls;
let ToggleControl = wp.components.ToggleControl;
let TextControl = wp.components.TextControl;

wp.blocks.registerBlockType("u3a/grouplist", {
    title: "u3a group list",
    description: "displays a list of all groups",
    icon: "groups",
    category: "widgets",
    attributes: {
      sort: {
        type: "string"
      },
      flow: {
        type: "string"
      },
      status: {
        type: "string"
      },
      bstatus: {
        type: "boolean"
      },
      when: {
        type: "string"
      },
      bwhen: {
        type: "boolean"
      }
    },
    edit: function( {attributes, setAttributes } ) {
      const { sort, flow, bstatus, status, bwhen, when } = attributes;
      const onChangeSort = val => {
        setAttributes( { sort: val });
      };
      const onChangeStatus = val => {
        setAttributes( { bstatus: val });
        if (val) {
          setAttributes( { status: "y"});
        } else {
          setAttributes( { status: "n"});
        }
      };
      const onChangeWhen = val => {
        setAttributes( { bwhen: val});
        if (val) {
          setAttributes( { when: "y"});
        } else {
          setAttributes( { when: "n"});
        }
      };
      const onChangeFlow = val => {
        setAttributes( { flow: val})
      };
    
      var nest = [
        wp.element.createElement(
          InspectorControls,
          {}, wp.element.createElement( PanelBody, {title:'Display options', initialOpen:false },
            wp.element.createElement( SelectControl,
              { label:'Sort Order', 
                value: sort,
                onChange: onChangeSort,
                options:[
                {
                  label: 'Alphabetic Order',
                  value: 'alpha',
                },
                {
                  label: 'By Category',
                  value: 'cat',
                },
                {
                  label: 'By Date',
                  value: 'day',
                },
                {
                  label: 'By Venue',
                  value: 'venue',
                }
                ]
              }
            ),
            wp.element.createElement( SelectControl,
              { label:'Alphabetic flow', 
                value: flow,
                onChange: onChangeFlow,
                options:[
                {
                  label: 'Down columns',
                  value: 'column',
                },
                {
                  label: 'Across rows',
                  value: 'row',
                }
               ]
              }
            ),
            wp.element.createElement( ToggleControl,
              { label:'Show Group Status', 
                checked: bstatus,
                onChange: onChangeStatus,
              }
            ),
            wp.element.createElement( ToggleControl,
              { label:'Show Meeting Time', 
                checked: bwhen,
                onChange: onChangeWhen,
              }
            )
          )
        ),
        wp.element.createElement("div", {className: 'wp-block alignwide', style: {color: 'white', backgroundColor: '#005ab8', padding: '10px'}}, "This placeholder shows where the list of groups will be shown.")
      ];
    return nest
    },
    save: function () {
      return null
    }
  })

  wp.blocks.registerBlockType("u3a/groupdata", {
    title: "u3a single group data",
    description: "displays group information",
    icon: "groups",
    category: "widgets",
    edit: function () {
      return wp.element.createElement("div", {style: {color: 'white', backgroundColor: '#005ab8', padding: '10px'}}, "This placeholder shows where the table of information held for this group will be shown.")
    },
    save: function () {
      return null
    }
  })