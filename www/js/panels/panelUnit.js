/*==============================================================================
    UNIT PANEL

==============================================================================*/

// class City extends PanelDiv {
class PanelUnit {
  constructor(id, data) {
    this.id = id
    this.data = data
  }
  title() {
    return `<div>UNIT = ${this.id}</div>`
  }
  content() {
    let content = 'Huge Content'
    content += ' ' + JSON.stringify(this.data)
    return content
  }
}
