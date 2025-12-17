

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
    /* The default values of these attributes are used in the edit function below.
       They are NOT stored in the HTML of the block unless setAttributes is used to set them.
       The same default values are set in the PHP code of the callback for the block.
    */
    attributes: {
      showtitle: {
        type: "string",
        default: "y"
      },
      linkonly: {
        type: "string",
        default: "n"
      },
      when: {
        type: "string",
        default: "future"
      },
      order: {
        type: "string",
        default: ""
      },
      event_cat: { /* backwards compatible string value */
        type: "string",
        default: ""
      },
      event_cats: { /* array value */
        type: "array",
        default: []
      },
      groups: {
        type: "string",
        default: "useglobal"
      },
      limitnum: {
        type: "integer",
        default: 0
      },
      limitdays: {
        type: "integer",
        default: 0
      },
      layout: {
        type: "string",
        default: "list"
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
      const { when, order, event_cat, event_cats, groups, crop, limitnum, limitdays, layout, bgcolor, showtitle, linkonly } = attributes;

      const InspectorControls = wp.blockEditor.InspectorControls;
      const PanelBody = wp.components.PanelBody;
      const SelectControl = wp.components.SelectControl;
      const NumberControl = wp.components.__experimentalNumberControl;
      const PanelColorSettings = wp.blockEditor.PanelColorSettings;
      const ToggleControl = wp.components.ToggleControl;
      const useSelect = wp.data.useSelect;
      const CheckboxControl = wp.components.CheckboxControl;
      const Scrollable = wp.components.__experimentalScrollable;
 
      const onChangeShowTitle = val => {
        bshowtitle = val;
        if (val) {
          setAttributes( { showtitle: "y"})
        } else {
          setAttributes( { showtitle: "n"})
        }
      };
      const onChangeLinkOnly = val => {
        blinkonly = val;
        if (val) {
          setAttributes( { linkonly: "y"})
        } else {
          setAttributes( { linkonly: "n"})
        }
      };
      const onChangeWhen = val => {
        setAttributes( { when: val });
        setAttributes( { order: (val == 'future' ? 'asc' : 'desc')});
      };
      const onChangeOrder = val => {
        setAttributes( { order: val });
      };

      const onChangeCat = Id => {
        catchoices[Id].checked = !catchoices[Id].checked;
        var newcats = [];
        if (Id == 0 && catchoices[Id].checked) {
          newcats = ['all'];
        } else {
          for (var i = 1; i < catchoices.length; i++) {
            if (catchoices[i].checked) {
              if (!newcats.includes(catchoices[i].slug)) {
                newcats.push(catchoices[i].slug);
              }
            }
          }
        }
        setAttributes( {event_cats: newcats});
      };

      const onChangeGroups = val => {
        setAttributes( { groups: val})
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

      /* backward compatibility with a single event_cat */
      if (event_cat.length !== 0) {
        if (event_cats.length === 0) {
          setAttributes( {event_cats: [event_cat], event_cat: ''});
        }
      }
      
      var catchoices = [];
      catchoices.push( { 
        element: 0,
        label:"All",
        slug:"all" , 
        checked:event_cats.includes('all'),
       } );
       for ( var i = 0; i < terms.length; i++ ) {
        catchoices.push( {
          element: i + 1,
          label:terms[i].name, 
          slug:terms[i].slug, 
          checked:event_cats.includes(terms[i].slug),
        } 
        );
      }
      
      const rendercatsarray = ( catchoices) => {
        return catchoices.map( 
          (catchoice)  => {
            return ( 
              wp.element.createElement( 
                CheckboxControl,
                {
                  Id: catchoice.element,
                  label: catchoice.label,
                  checked: catchoice.checked,
                  onChange: () => { 
                    const Id = catchoice.element;
                    onChangeCat(Id);
                  }
                }
              )
            )
          }
        )
      }


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
      var bshowtitle = (showtitle == 'y');
      var blinkonly = (linkonly  == 'y');
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
              )
            ),
            wp.element.createElement( PanelBody, {title:'Category Selection' , initialOpen:false},
              wp.element.createElement( Scrollable, { 
                children: wp.element.createElement("div", {style: {padding: '10px', height: 300 }}, 
                  rendercatsarray(catchoices)
                )
              }
              )
            ),
            wp.element.createElement( PanelBody, {title:'Limits', initialOpen:false },
              wp.element.createElement(SelectControl,
                { label:'Show group events',
                  value: groups,
                  onChange: onChangeGroups,
                  options:[
                  {
                    label: 'Use the value set in u3a settings',
                    value: 'useglobal',
                  },
                  {
                    label: 'Exclude group events',
                    value: 'n',
                  },
                  {
                    label: 'Include group events',
                    value: 'y',
                  }
                  ]
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
              wp.element.createElement( ToggleControl,
                { label:'Show Title', 
                  checked: bshowtitle,
                  onChange: onChangeShowTitle,
                }
              ),
              wp.element.createElement( ToggleControl,
                { label:'Show Link Only', 
                  checked: blinkonly,
                  onChange: onChangeLinkOnly,
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
