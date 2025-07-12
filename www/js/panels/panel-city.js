/*==============================================================================
    CITY PANEL

==============================================================================*/

class PanelCity {
  constructor(id, data) {
    this.id = id
    this.data = data
    this.currentOrder = null
    this.preparingOrder = false
    this.selectedUnitType = null
    this.constructionQueue = data.construction_queue || []
  }

  title() {
    return `<div>${this.data.na || this.id}</div>`
  }

  content() {
    return `  
            <div class="city-panel">  
                <div class="city-info">  
                    <h3>${this.data.na}</h3>  
                    <p>${this.data.po || 0} inhabitants</p>  
                </div>  
                  
                <div class="unit-construction">  
                    <h4>Construction d'unité</h4>  
                    ${this.renderUnitSelection()}  
                    ${this.renderConstructionQueue()}  
                    ${this.renderConstructionControls()}  
                </div>  
            </div>  
        `
  }

  renderUnitSelection() {
    const availableUnits = this.getAvailableUnits()
    const options = availableUnits
      .map(
        (unit) =>
          `<option value="${unit.id}" data-turns="${unit.turn}" data-cost="${unit.cost}">  
                ${unit.name} (${unit.turn} turns, ${unit.cost} items)  
            </option>`
      )
      .join('')

    return `  
            <div class="unit-selector">  
                <select id="unit-type-select">  
                    <option value="">Choose unit</option>  
                    ${options}  
                </select>  
            </div>  
        `
  }

  renderConstructionQueue() {
    if (this.constructionQueue.length === 0) {
      return '<div class="queue-empty">No current unit recruitment</div>'
    }

    const queueItems = this.constructionQueue
      .map(
        (item) => `  
            <div class="queue-item">  
                <span class="unit-name">${item.name}</span>  
                <span class="time-remaining">${item.turns_remaining} turn remaining</span>  
                <button class="cancel-btn" data-order-id="${item.order_id}">×</button>  
            </div>  
        `
      )
      .join('')

    return `  
            <div class="construction-queue">  
                <h5>Construction queue</h5>  
                ${queueItems}  
            </div>  
        `
  }

  renderConstructionControls() {
    return `  
            <div class="construction-controls">  
                <button id="start-construction" disabled>Build</button>  
            </div>  
        `
  }

getAvailableUnits() {  
    const units = [];  
    if (Rules && Rules.units) {  
        Rules.units.forEach(unit => {  
            if (this.canBuildUnit(unit)) {  
                units.push({  
                    id: unit.id,  
                    name: unit.name,  
                    turn: unit.turn,  
                    cost: unit.cost?.gold || 0  
                });  
            }  
        });  
    }  
    return units;  
}

  canBuildUnit(unit) {
    // Vérifie si la ville peut construire cette unité
    // Basé sur les bâtiments requis dans les données de la ville
    if (!unit.requisite?.buildings) return true

    const cityBuildings = this.data.bu || {}
    for (const building in unit.requisite.buildings) {
      const required = unit.requisite.buildings[building]
      const available = cityBuildings[building] || 0
      if (available < required) return false
    }
    return true
  }

  setupEvents() {
    // Gestion de la sélection d'unité
    $('#unit-type-select').on('change', (e) => {
      const unitId = e.target.value
      const startBtn = $('#start-construction')

      if (unitId) {
        this.selectedUnitType = unitId
        startBtn.prop('disabled', false)
      } else {
        this.selectedUnitType = null
        startBtn.prop('disabled', true)
      }
    })

    // Gestion du bouton de construction
    $('#start-construction').on('click', () => {
      if (this.selectedUnitType) {
        this.startUnitConstruction(this.selectedUnitType)
      }
    })

    // Gestion de l'annulation d'ordres
    $('.cancel-btn').on('click', (e) => {
      const orderId = $(e.target).data('order-id')
      this.cancelConstructionOrder(orderId)
    })
  }

startUnitConstruction(unitType) {  
    // Format : CITY_ID-RECRUIT-UNIT_TYPE  
    const orderString = `${this.id}-RECRUIT-${unitType}`;  
      
    C.ask_data('SET_ORDER', orderString, (response) => {  
        if (response && response.length > 0) {  
            // Actualise l'affichage avec les nouvelles données  
            this.addSelectedInfo(response);  
        } else {  
            alert('Erreur lors de la création de l\'ordre');  
        }  
    });  
}  
  
cancelConstructionOrder(orderId) {  
    C.ask_data('CANCEL_ORDER', orderId, (response) => {  
        if (response && response.length > 0) {  
            this.addSelectedInfo(response);  
        }  
    });  
}  
  
// Ajouter cette méthode pour traiter la réponse serveur  
addSelectedInfo(data) {  
    if (data && data.length > 0) {  
        // Mettre à jour les données de la ville avec la réponse  
        this.data = data[0];  
        // Redessiner le contenu du panneau  
        $('#panel_content').html(this.content());  
        this.setupEvents();  
    }  
}
  
}
