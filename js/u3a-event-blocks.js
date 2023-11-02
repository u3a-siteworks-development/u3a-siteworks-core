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
    description: "displays list of events",
    icon: "tickets-alt",
    category: "widgets",
    edit: function () {
      return wp.element.createElement("div", {style: {color: 'black', backgroundColor: '#ffc700', padding: '10px'}}, "This placeholder shows where a table of events will be shown.")
    },
    save: function () {
      return null
    }
  })
