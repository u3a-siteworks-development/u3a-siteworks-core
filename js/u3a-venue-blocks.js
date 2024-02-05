wp.blocks.registerBlockType("u3a/venuelist", {
    title: "u3a venue list",
    description: "displays a list of venues",
    icon: "building",
    category: "widgets",
    edit: function () {
      return wp.element.createElement("div", {style: {color: 'white', backgroundColor: '#005ab8', padding: '10px'}}, "This placeholder shows where a list of venues will be shown.")
    },
    save: function () {
      return null
    }
  })

  wp.blocks.registerBlockType("u3a/venuedata", {
    title: "u3a single venue data",
    description: "displays data for this venue",
    icon: "building",
    category: "widgets",
    edit: function () {
      return wp.element.createElement("div", {style: {color: 'white', backgroundColor: '#005ab8', padding: '10px'}}, "This placeholder shows where the venue information will be shown.")
    },
    save: function () {
      return null
    }
  })