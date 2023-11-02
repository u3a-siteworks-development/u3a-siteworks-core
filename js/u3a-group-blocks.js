wp.blocks.registerBlockType("u3a/grouplist", {
    title: "u3a group list",
    description: "displays a list of all groups",
    icon: "groups",
    category: "widgets",
    edit: function () {
      return wp.element.createElement("div", {className: 'wp-block alignwide', style: {color: 'white', backgroundColor: '#005ab8', padding: '10px'}}, "This placeholder shows where the list of groups will be shown.")
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