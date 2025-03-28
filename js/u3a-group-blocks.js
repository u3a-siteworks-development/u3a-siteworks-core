wp.blocks.registerBlockType("u3a/grouplist", {
    title: "u3a group list",
    description: "displays a list of all groups",
    icon: "groups",
    category: "widgets",
    attributes: {
      group_cat: {
        type: "string",
        default: "all"
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
      const { group_cat, sort, flow, bstatus, group_status, bwhen, when, bvenue, venue } = attributes;

      const InspectorControls = wp.blockEditor.InspectorControls;
      const PanelBody = wp.components.PanelBody;
      const SelectControl = wp.components.SelectControl;
      const ToggleControl = wp.components.ToggleControl;
      const useSelect = wp.data.useSelect;

      const onChangeGroupCat = val => {
        setAttributes( { group_cat: val });
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
      var catlist = [];
      catlist.push( {
         label: 'All categories',
         value: 'all'
      } );
      for ( var i = 0; i < terms.length; i++ ) {
          catlist.push( {
              label: terms[i].name,
              value: terms[i].slug
          } );
      };

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
          {}, wp.element.createElement( PanelBody, {title:'Display options', initialOpen:true },
              wp.element.createElement( SelectControl,
                { label:'Category', 
                  value: group_cat,
                  help: 'Either all categories or a single category',
                  onChange: onChangeGroupCat,
                  options: catlist
                }
              ),
            wp.element.createElement(ShowOrNot,
              { show:('all' == group_cat),
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