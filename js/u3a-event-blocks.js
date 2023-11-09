


let NumberControl = wp.components.__experimentalNumberControl;
let PanelBody = wp.components.PanelBody;
let SelectControl = wp.components.SelectControl;
let TextControl = wp.components.TextControl;
let InspectorControls = wp.editor.InspectorControls;
let useBlockProps = wp.editor.useBlockProps;
let useSelect = wp.data.useSelect;
//let InnerBlock = wp.editor.InnerBlock;

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
      limitnum: {
        type: "integer"
      },
      limitdays: {
        type: "integer"
      }

    },
    edit: function( {attributes, setAttributes } ) {
      const { when, order, cat, groups, limitnum, limitdays } = attributes;
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
        setAttributes( { groups: val})
      };
      const onChangeNum = val => {
        setAttributes( { limitnum: Number(val)})
      };
      const onChangeDays = val => {
        setAttributes( { limitdays: Number(val)})
      };

      const query = {
                per_page: -1,
                orderby: 'name',
                order: 'asc',
                _fields: 'id,name,slug'
            };
      const terms = useSelect( ( select ) =>
            select( 'core' ).getEntityRecords( 'taxonomy', 'u3a_event_category', query )
        );
      if ( ! terms ) {
          return 'Loading...';
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

      var nest = [
          wp.element.createElement(
            InspectorControls,
            {}, wp.element.createElement( PanelBody, {}, 
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
              ),
              wp.element.createElement( SelectControl,
                { label:'Include Groups', 
                  value: groups,
                  onChange: onChangeGroups,
                  options:[
                  {
                    label: 'Included',
                    value: 'y',
                  },
                  {
                    label: 'Excluded',
                    value: 'n',
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
              ),
    
            )
         ),
        wp.element.createElement("div", {style: {color: 'black', backgroundColor: '#ffc700', padding: '10px'}}, "This placeholder shows where a table of events will be shown.")
      ];
      return  nest
    },
    save: function ( { attributes } ) {
      return wp.element.createElement('p', {}, attributes ); 
    }
  })
