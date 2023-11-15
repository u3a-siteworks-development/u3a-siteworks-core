
wp.blocks.registerBlockType("u3a/grouplist", {
    title: "u3a group list",
    description: "displays a list of all groups",
    icon: "groups",
    category: "widgets",
    attributes: {
      sort: {
        type: "string"
      },
      status: {
        type: "string"
      },
      when: {
        type: "string"
      },
      bgcolor: {
        type: "string",
        default: "#005ab8"
      }
    },
    edit: function( {attributes, setAttributes } ) {
      const { sort, status, when, bgcolor } = attributes;
      const onChangeSort = val => {
        setAttributes( { sort: val });
      };
      const onChangeStatus = val => {
        setAttributes( { status: val });
      };
      const onChangeWhen = val => {
        setAttributes( { when: val})
      };
      const onChangeBGColor = val => {
        setAttributes( { bgcolor: val})
      };
      const colorSettingsDropDown =
        [
          {
            value: bgcolor,
            onChange: onChangeBGColor,
            label: 'Background colour' ,
          },
        ];

      var nest = [
        wp.element.createElement(
          InspectorControls,
          {}, wp.element.createElement( PanelBody, {title:'Sort Style', initialOpen:false }, 
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
                  value: 'vanue',
                }
                ]
              }
            ),
            wp.element.createElement( SelectControl,
              { label:'Include Group Status', 
                value: status,
                onChange: onChangeStatus,
                options:[
                {
                  label: 'Yes',
                  value: 'y',
                },
                {
                  label: 'No',
                  value: 'n',
                }
                ]
              }
            ),
            wp.element.createElement( SelectControl,
              { label:'Include Meeting Time', 
                value: when,
                onChange: onChangeWhen,
                options:[
                {
                  label: 'Yes',
                  value: 'y',
                },
                {                    
                  label: 'No',
                   value: 'n',
                }
                ]
              }
            )
          ),
          wp.element.createElement( PanelColorSettings, {title:'Colours', initialOpen:false, colorSettings:colorSettingsDropDown }, 
          )
        ),
        wp.element.createElement("div", {className: 'wp-block alignwide', style: {color: 'white', backgroundColor: bgcolor, padding: '10px'}}, "This placeholder shows where the list of groups will be shown.")
      ];
    return  nest
    },
    save: function ( { attributes } ) {
      return wp.element.createElement('p', {}, attributes ); 
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