/*==============================================================================
    UNIT PANEL

==============================================================================*/

// class City extends PanelDiv {
class PanelUnit {
	constructor(id, data) {
		this.id = id;
		this.data = data;
	}
	title() {
		return `<div>UNIT = ${this.id}</div>`;	
	}
	content() {
		return `Nice Content`;	
	}
	
}
