wp.blocks.registerBlockType("u3a/grouplist", {
    title: "u3a group list",
    description: "displays a list of all groups",
    icon: "groups",
    category: "widgets",
    attributes: {
      group_cat: { /* backwards compatible string */
        type: "string",
        default: ""
      },
      group_cats: { /* cats now an array */
        type: "array",
        default: []
      },
      sort: {
        type: "string"
      },
      flow: {
        type: "string"
      },
      group_status: {
        type: "string"
      },
      bstatus: {
        type: "boolean",
        default: true
      },
      when: {
        type: "string"
      },
      bwhen: {
        type: "boolean",
        default: true
      },
      venue: {
        type: "string"
      },
      bvenue: {
        type: "boolean",
        default: false
      }
    },
    edit: function( {attributes, setAttributes } ) {
      const { group_cat, group_cats, sort, flow, bstatus, group_status, bwhen, when, bvenue, venue } = attributes;

      const InspectorControls = wp.blockEditor.InspectorControls;
      const PanelBody = wp.components.PanelBody;
      const SelectControl = wp.components.SelectControl;
      const ToggleControl = wp.components.ToggleControl;
      const useSelect = wp.data.useSelect;
      const CheckboxControl = wp.components.CheckboxControl;
      const Scrollable = wp.components.__experimentalScrollable;

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
        setAttributes( {group_cats: newcats});
      };

      const onChangeSort = val => {
        setAttributes( { sort: val });
      };
      const onChangeGroupStatus = val => {
        setAttributes( { bstatus: val });
        if (val) {
          setAttributes( { group_status: "y"});
        } else {
          setAttributes( { group_status: "n"});
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
      const onChangeVenue = val => {
        setAttributes( { bvenue: val});
        if (val) {
          setAttributes( { venue: "y"});
        } else {
          setAttributes( { venue: "n"});
        }
      };
      const onChangeFlow = val => {
        setAttributes( { flow: val})
      };

    /* get all the group categories */
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
            select( 'core' ).getEntityRecords( 'taxonomy', 'u3a_group_category', query )
        );
      if ( ! terms ) {
          return 'Loading, please wait...';
      }
      if ( terms.length === 0 ) {
          return 'No terms found';
      }

      /* backwards compatibility */
      if (group_cat.length  !== 0) {
        if (group_cats.length === 0) {
          setAttributes( {group_cats: [group_cat], group_cat: ''});
        }
      }

      var catchoices = [];
      
      catchoices.push( { 
        element: 0,
        label:"All",
        slug:"all" , 
        checked:group_cats.includes('all'),
       } );
       for ( var i = 0; i < terms.length; i++ ) {
        catchoices.push( {
          element: i + 1,
          label:terms[i].name.replace(/&amp;/g, '&'),
          slug:terms[i].slug, 
          checked:group_cats.includes(terms[i].slug),
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
      /* function ShowOrNot
         show is a boolean, and el is a wp.element which is returned if show is true.*/
      function ShowOrNot(params){
            const { show, el } = params;
            if (!show ) {
              return null;
          }
          return el;
      }

      var nest = [
        wp.element.createElement(
          InspectorControls,
          {}, 
            wp.element.createElement( PanelBody, {title:'Category Selection' , initialOpen:false},
              wp.element.createElement( Scrollable, { 
                children: wp.element.createElement("div", {style: {padding: '10px', height: 300 }}, 
                  rendercatsarray(catchoices)
                )
              }
              )
            ),
            wp.element.createElement( PanelBody, {title:'Display options', initialOpen:true },
            wp.element.createElement(ShowOrNot,
              { show:(group_cats.length != 1 || 'all' == group_cats[0]),
                el: wp.element.createElement( SelectControl,
                      { label:'Sort Order', 
                        value: sort,
                        onChange: onChangeSort,
                        options:[
                          {label: 'Alphabetic Order', value: 'alpha'},
                          {label: 'By Category', value: 'cat'},
                          {label: 'By Date', value: 'day'},
                          {label: 'By Venue', value: 'venue'}
                        ]
                      }
                    )
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
                onChange: onChangeGroupStatus,
              }
            ),
            wp.element.createElement( ToggleControl,
              { label:'Show Meeting Time', 
                checked: bwhen,
                onChange: onChangeWhen,
              }
            ),
            wp.element.createElement( ToggleControl,
              { label:'Show Venue',
                checked: bvenue,
                onChange: onChangeVenue,
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