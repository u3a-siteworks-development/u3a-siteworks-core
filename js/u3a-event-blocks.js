
wp.blocks.registerBlockType("u3a/eventdata", {
    title: "u3a single event data",
    description: "displays details of this event",
    icon: "tickets-alt",
    category: "widgets",
    edit: function () {
      return wp.element.createElement("div", {style: {color: 'black', backgroundColor: '#ffc700', padding: '10px'}}, "This placeholder shows where the event information will be shown.")
    },
    save: function () {
      return null
    }
  })

  wp.blocks.registerBlockType("u3a/eventlist", {
    title: "u3a events list",
    description: "Displays list of events",
    icon: "tickets-alt",
    category: "widgets",
    attributes: {
      when: {
        type: "string"
      },
      order: {
        type: "string"
      },
      cat: {
        type: "string"
      },
      groups: {
        type: "string"
      },
      bgroups: {
        type: "boolean"
      },
      limitnum: {
        type: "integer"
      },
      limitdays: {
        type: "integer"
      },
      layout: {
          type: "string"
      },
      crop: {
        type: "string",
        default: "y"
      },
      bgcolor: {
        type: "string"
      },
    },
    edit: function( {attributes, setAttributes } ) {
      const { when, order, cat, bgroups, groups, crop, limitnum, limitdays, layout, bgcolor } = attributes;
      const onChangeWhen = val => {
        setAttributes( { when: val });
        setAttributes( { order: (val == 'future' ? 'asc' : 'desc')});
      };
      const onChangeOrder = val => {
        setAttributes( { order: val });
      };
      const onChangeCat = val => {
        setAttributes( { cat: val})
      };
      const onChangeGroups = val => {
        setAttributes( { bgroups: val})
        if (val) {
          setAttributes( { groups: "y"})
        } else {
          setAttributes( { groups: "n"})
        }
      };
      const onChangeCrop = val => {
          bcrop = val;
        if (val) {
          setAttributes( { crop: "y"})
        } else {
          setAttributes( { crop: "n"})
        }
      };
      const onChangeNum = val => {
        setAttributes( { limitnum: Number(val)})
      };
      const onChangeDays = val => {
        setAttributes( { limitdays: Number(val)})
      };
      const onChangeLayout = val => {
        setAttributes( { layout: val } )
        setAttributes( { bgcolor: (val == 'list' ? '#ffc700' : '#63c369')}); // grid default is uta-light-green
      }
      const onChangeBGColor = val => {
        setAttributes( { bgcolor: val } )
      }
      const colorSettingsDropDown =
        [
          {
            label: 'Grid background',
            value: bgcolor,
            onChange: onChangeBGColor,
          },
        ];
      const query = {
                /* The default context is 'edit' and authors don't have 'edit' capability on the taxonomy.
                   adding the following line cures this problem in the REST call made by getEntityRecords() */
                context: 'view',
                per_page: -1,
                orderby: 'name',
                order: 'asc',
                _fields: 'id,name,slug'
            };
      const terms = useSelect( ( select ) =>
            select( 'core' ).getEntityRecords( 'taxonomy', 'u3a_event_category', query )
        );
      if ( ! terms ) {
          return 'Loading, please wait...';
      }
      if ( terms.length === 0 ) {
          return 'No terms found';
      }
      var catlist = [];
      catlist.push( {
         label: 'All categories',
         value: ''
      } );
      for ( var i = 0; i < terms.length; i++ ) {
          catlist.push( {
              label: terms[i].name,
              value: terms[i].slug
          } );
      };
      function ShowGridOptions(params){
          const {gridOn, cropOn, ...panelParams} = params;
          if (gridOn) {
              return wp.element.createElement("div", {},
                         wp.element.createElement( ToggleControl,
                          { label:'Crop image to fit',
                            checked: cropOn,
                            onChange: onChangeCrop,
                          }
                         ),
                         wp.element.createElement(PanelColorSettings, panelParams),
                     );
          }
          return '';
      }
      /* default to #ffc700 if layout == 'list' or is not set*/
      var editBoxColor = (layout == 'grid') ? bgcolor : '#ffc700';
      var bcrop = (crop == 'y');

      var nest = [
          wp.element.createElement(
            InspectorControls,
            {}, wp.element.createElement( PanelBody, {title:'Sort and Filter', initialOpen:false }, 
              wp.element.createElement( SelectControl,
                { label:'When', 
                  value: when,
                  onChange: onChangeWhen,
                  options:[
                  {
                    label: 'Future',
                    value: 'future',
                  },
                  {
                    label: 'Past',
                    value: 'past',
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
                { label:'Category', 
                  value: cat,
                  help: 'Either all categories or chosen category',
                  onChange: onChangeCat,
                  options: catlist
                }
              )
            ),
            wp.element.createElement( PanelBody, {title:'Limits', initialOpen:false },
              wp.element.createElement( ToggleControl,
                { label:'Show group events',
                  checked: bgroups,
                  onChange: onChangeGroups,
                }
              ),
              wp.element.createElement( NumberControl,
                { label:'Limit Number of Entries', 
                  value: limitnum,
                  onChange: onChangeNum,
                }
              ),
              wp.element.createElement( NumberControl,
                { label:'Limit Number of Days', 
                  value: limitdays,
                  help: 'e.g. upto 90 days in future/past',
                  onChange: onChangeDays,
                }
              )
            ),
            wp.element.createElement( PanelBody, {title:'Layout Choices', initialOpen:false },
              wp.element.createElement( SelectControl,
                { label:'Layout', 
                  value: layout,
                  onChange: onChangeLayout,
                  options:[
                    {label: 'Simple list', value: 'list',},
                    {label: 'Grid with featured image', value: 'grid',}
                  ]
                }
              ),
              wp.element.createElement( ShowGridOptions,
                   {gridOn:(layout=='grid'),
                    cropOn: (crop == 'y'),
                    title:'Colours', initialOpen:false, colorSettings:colorSettingsDropDown 
                   }, 
              ),
            ),
          ),
        wp.element.createElement("div", {style: {color: 'black', backgroundColor: editBoxColor, padding: '10px', border: '1px solid lightgrey'}}, "This placeholder shows where a table of events will be shown.")
      ];
      return  nest
    },
    save: function () {
      return null; 
    }
  })
