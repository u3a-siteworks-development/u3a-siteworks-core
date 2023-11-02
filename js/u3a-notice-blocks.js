wp.blocks.registerBlockType("u3a/noticelist", {
    title: "u3a notice list",
    description: "Displays current notices",
    icon: "testimonial",
    category: "widgets",
    edit: function () {
      return wp.element.createElement("div", {className: 'wp-block alignwide', style: {color: 'white', backgroundColor: '#005ab8', padding: '10px'}}, "This placeholder shows where the latest notices will be shown.")
    },
    save: function () {
      return null
    }
  })
