/*==============================================================================
    CITY PANEL

==============================================================================*/

class PanelCity {
  constructor(id, data) {
    this.id = id
    this.data = data

    this.currentOrder = null
    this.preparingOrder = false

    /*
		this.order = Or.order_from_server(units, units[0].or);
		this.currentOrder = Or.order_from_gui(units, order_name);
		*/
  }

  title() {
    return `<div>CITY = ${this.id}</div>`
  }

  content() {
    let content = 'Huge Content'
    content += ' ' + JSON.stringify(this.data)
    return content
  }
}
