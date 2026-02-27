/* NOT IMPLEMENTED
wp.blocks.registerBlockType("u3a/venuelist", {
    apiVersion: 3,  
    title: "u3a venue list",
    description: "displays a list of venues",
    icon: "building",
    category: "widgets",
    edit: function () {
      const useBlockProps = wp.blockEditor.useBlockProps;
      const blockProps = useBlockProps();
      return wp.element.createElement("div", blockProps,
        wp.element.createElement("div", {style: {color: 'white', backgroundColor: '#005ab8', padding: '10px'}}, "This placeholder shows where a list of venues will be shown.")
      )
    },
    save: function () {
      return null
    }
  })
*/

  wp.blocks.registerBlockType("u3a/venuedata", {
    apiVersion: 3,
    title: "u3a single venue data",
    description: "displays data for this venue",
    icon: "building",
    category: "widgets",
    edit: function () {
      const useBlockProps = wp.blockEditor.useBlockProps;
      const blockProps = useBlockProps();
      return wp.element.createElement("div", blockProps,
        wp.element.createElement("div", {style: {color: 'white', backgroundColor: '#005ab8', padding: '10px'}}, "This placeholder shows where the venue information will be shown.")
      )
    },
    save: function () {
      return null
    }
  })