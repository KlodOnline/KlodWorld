/*==============================================================================
    Selection Module

    SELECTION DES UNITES
        Vue partielle, permetaant de voir l'ordre en cours ou ceux dispos
            -> Ordre en cours
            -> Ordre dispos
        Le statut est résumé
            -> Nom
            -> Moral
            -> Icone comportement (agressif/passif, auto-unload ...?)
        Un lien vers la fiche de l'unité existe
            -> "Loupe"

    SELECTION DES VILLES
        Le cadre des unités (à la place des ordre)
            -> selectionnables
        Statut :
            -> Nom ?
            -> Population ?
            -> Statut de la production, "%" et Delta T.
        Un lien vers la fiche de la ville existe.

    --> PAS de gestion des piles : Une unité seulement par case sauf en ville
    -> ou lae cartouche de la ville permet de choisir l'unité à utiliser.

    INTERFACE :
        -> Ce qui est appellable par le reste du GUI

==============================================================================*/
'use strict'
/*==============================================================================
 ██████╗ █████╗ ██████╗ ████████╗ ██████╗ ██╗   ██╗ ██████╗██╗  ██╗███████╗
██╔════╝██╔══██╗██╔══██╗╚══██╔══╝██╔═══██╗██║   ██║██╔════╝██║  ██║██╔════╝
██║     ███████║██████╔╝   ██║   ██║   ██║██║   ██║██║     ███████║█████╗  
██║     ██╔══██║██╔══██╗   ██║   ██║   ██║██║   ██║██║     ██╔══██║██╔══╝  
╚██████╗██║  ██║██║  ██║   ██║   ╚██████╔╝╚██████╔╝╚██████╗██║  ██║███████╗
 ╚═════╝╚═╝  ╚═╝╚═╝  ╚═╝   ╚═╝    ╚═════╝  ╚═════╝  ╚═════╝╚═╝  ╚═╝╚══════╝
	
	This is objects manipulating DOM - they are used by selection.js to render

==============================================================================*/
class Cartouche {
  constructor(tag) {
    this.tag = tag
    this.redraw = false
    this.title = 'Empty title'
  }
  delete() {
    $('#selection_container').remove()
  }
  selectionFrame(selectionDisplay = '') {
    // The main Frame of selections !
    return `
			<div id="selection_container">
                <div id="selection_title">
                    ${this.title}
                </div>
                <div id="selection_display">
                    ${selectionDisplay}
                </div>
            </div>`
  }
}

class CityCartouche extends Cartouche {
  constructor(tag, city) {
    super(tag)
    this.city = city
    let info_icon = `<input id="${city.id}" class="info CI" title="City details" type="button">`
    this.title = info_icon + ` ${city.na} (${city.id})`
  }
  draw() {
    // Two div, one for a list of unit, one for the city icon
    const selectionDisplay = `
			<div class="token_c"><div id="u-${this.tag}" class=""></div></div>
            <div class="city_c"><div id="c-${this.tag}" class=""></div></div>`
    $('#selection').html(this.selectionFrame(selectionDisplay))
    this.drawMain()
    this.drawInside()
  }
  drawMain() {
    // Main is a city Icon
    if (!this.city.id) return
    const cityHTML = `
            <img id="${this.city.id}" 
                class="tok CS ${helper_color_class(this.city.co)}" 
                src="./u/c0.png" 
            />
        `
    $(`#c-${this.tag}`).prepend(cityHTML)
  }
  drawInside() {
    logMessage('Drawing inside for CityCartouche (raw)')

    if (this.city.units == null) {
      return
    }

    const container = $(`#u-${this.tag}`)
    container.empty() // Efface tout le contenu précédent

    for (let unit of this.city.units) {
      if (!unit.id) continue

      const unitHTML = `
	            <img id="${unit.id}" 
	                class="tok US ${helper_color_class(this.city.co)}" 
	                src="./u/u${unit.ty}.png" 
	            />
	        `
      container.append(unitHTML) // Ajoute chaque unité proprement
    }
  }
  allIds() {
    return [this.city.id]
  }
}

class UnitCartouche extends Cartouche {
  constructor(tag, units) {
    super()
    this.tag = tag
    this.units = units
    this.redraw = false
    if (units.length > 1) {
      this.title = 'MULTIPLE SELECTION'
    } else if (units[0]) {
      let info_icon = `<input id="${units[0].id}" class="info UI" title="Unit details" type="button">`
      this.title = info_icon + ` ${units[0].na} (${units[0].id})`
    }
  }
  illegal_place(legal_place) {
    return this.units.some((unit) => unit.cr !== legal_place)
  }
  draw() {
    // Two div, one for a list of order(s), one for some unit(s) icon(s)
    const selectionDisplay = `
			<div class="order_c"><div id="o-${this.tag}" class=""></div></div>
			<div class="token_c"><div id="u-${this.tag}" class=""></div></div>`
    $('#selection').html(this.selectionFrame(selectionDisplay))
    this.drawMain()
    this.drawInside()
  }
  drawMain() {
    // Main is Unit(s) Icon(s)
    for (let unit of this.units) {
      $('#u-' + this.tag).prepend(
        `<img id="${unit.id}" class="tok US ${helper_color_class(unit.co)}" src="./u/u${unit.ty}.png" />`
      )
    }
  }
  drawInside() {
    // Inside depends of Order diplay, in childs Classes
    const container = $(`#o-${this.tag}`)
    container.html('No order system for ya ?!')
  }
  allIds() {
    return this.units.map((unit) => unit.id)
  }
}

class CartOrder extends UnitCartouche {
  constructor(tag, order, units) {
    super(tag, units)
    this.order = order
  }
  drawInside() {
    logMessage('Drawing inside for CartOrder')
    const container = $(`#o-${this.tag}`)
    container.html(this.order.txt())
  }
}

class CartPrep extends UnitCartouche {
  constructor(tag, order, units) {
    super(tag, units)
    this.order = order
  }
  drawInside() {
    logMessage('Drawing inside for CartPrep')
    const container = $(`#o-${this.tag}`)
    container.html(this.order.prep_txt())
  }
}

class CartNone extends UnitCartouche {
  constructor(tag, units) {
    super(tag, units)
    this.order = null
  }

  drawInside() {
    logMessage('Drawing inside for CartNone')
    const container = $(`#o-${this.tag}`)
    container.empty()

    let allOrders = []

    // Récupère les ordres disponibles pour chaque unité
    for (const unit of this.units) {
      const unitOrders = Rules.units[unit.ty]?.orders?.split(',') || []
      allOrders = allOrders.concat(unitOrders)
    }

    // Affiche uniquement les ordres uniques
    const uniqueOrders = [...new Set(allOrders)]

    // Ajoute chaque ordre dans le DOM
    uniqueOrders.forEach((order) => {
      const orderElement = `
                <div id="${order}" class="ico O ${order}" title="${order}"></div>
            `
      container.append(orderElement)
    })
  }

  // Trouver les ordres communs dans plusieurs tableaux
  static diff(arrays) {
    return arrays.length
      ? arrays.reduce((common, array) => common.filter((order) => array.includes(order)))
      : []
  }
}

/*==============================================================================
███╗   ███╗ ██████╗ ██████╗ ██╗   ██╗██╗     ███████╗
████╗ ████║██╔═══██╗██╔══██╗██║   ██║██║     ██╔════╝
██╔████╔██║██║   ██║██║  ██║██║   ██║██║     █████╗  
██║╚██╔╝██║██║   ██║██║  ██║██║   ██║██║     ██╔══╝  
██║ ╚═╝ ██║╚██████╔╝██████╔╝╚██████╔╝███████╗███████╗
╚═╝     ╚═╝ ╚═════╝ ╚═════╝  ╚═════╝ ╚══════╝╚══════╝
==============================================================================*/
class Selection {
  constructor() {
    this.selectedEntities = []
    this.cartouches = []
    this.currentOrder = null
    this.preparingOrder = false
    this.target = null
    this.initEvents()
  }

  /*--------------------------------------------------------------------------
		INTERNALS
	--------------------------------------------------------------------------*/
  addSelectedInfo(data = null) {
    if (data !== null) {
      logMessage('Info from the server :')
      logMessage(JSON.stringify(data))
      this.digestData(data)
      this.genCartouches()
    }
    this.drawCartouches()
  }
  digestData(data) {
    logMessage('Processing data...')
    const units = []
    let city = null
    for (const key in data) {
      const entity = data[key]
      if (entity.t === 'U') {
        // Process a unit
        units.push(new Unit(entity.cr, entity, ''))
        // Optionally store in cache
        // this.cache['unit'][entity.id] = unit;
      } else if (entity.t === 'C') {
        // Process a city (ensures only one city is stored)
        city = new City(entity.cr, entity, '')
        // Optionally store in cache
        // this.cache['city'][city.id] = city;
      }
    }
    // Update selected entities
    // If a city exists, prioritize it
    if (city) {
      this.selectedEntities = [city]
    }
    // Otherwise, use the units
    else if (units.length > 0) {
      this.selectedEntities = units
    }
    // No entities to select
    else {
      this.selectedEntities = []
    }
    logMessage(`Selection updated: ${JSON.stringify(this.selectedEntities)}`)
  }

  /*==============================================================================
 ██████╗ █████╗ ██████╗ ████████╗███████╗
██╔════╝██╔══██╗██╔══██╗╚══██╔══╝██╔════╝
██║     ███████║██████╔╝   ██║   ███████╗
██║     ██╔══██║██╔══██╗   ██║   ╚════██║
╚██████╗██║  ██║██║  ██║   ██║   ███████║
 ╚═════╝╚═╝  ╚═╝╚═╝  ╚═╝   ╚═╝   ╚══════╝
==============================================================================*/
  existingCarts() {
    if (!Array.isArray(this.cartouches)) {
      logMessage('cartouches is null or undefined.')
      return
    }
    this.cartouches.forEach((cartouche, index) => {
      logMessage(` ${index} : ${cartouche.constructor.name}`)
    })
  }

  /*--------------------------------------------------------------------------
		MANIPULATIONS
	--------------------------------------------------------------------------*/
  deleteCartouches() {
    this.cartouches.forEach((cartouche) => cartouche.delete())
  }
  genCartouches() {
    logMessage('(re)Generating cartouche(s)...')
    // Vérifie qu'au moins une entité est sélectionnée
    if (!this.selectedEntities?.length) {
      logMessage('No entities selected.')
      return
    }
    logMessage('Deleting cartouches value')
    this.cartouches = []
    // Calcul du hash (centralisé)
    const hash = entitiesIdString(this.selectedEntities)
    logMessage(`Hash: ${hash}`)
    // Génération du cartouche pour la première entité valide
    const entity = this.selectedEntities[0] // Priorité à la première entité
    const className = getClassName(entity)
    switch (className) {
      case 'Unit':
        this.genUnitCartouches(hash)
        break
      case 'City':
        this.genCityCartouches(hash)
        break
      default:
        logMessage(`Unknown entity type: ${className}`)
        console.log(JSON.stringify(entity))
    }
  }
  genUnitCartouches(hash) {
    logMessage('Generating unit cartouches...')

    // Séparation des unités en fonction de leur état
    this.cartouches = []

    const noOrders = []
    const prepOrders = []
    const withOrders = {}

    for (const unit of Object.values(this.selectedEntities)) {
      if (unit.or == null) {
        // Séparation entre unités sans ordre et en préparation
        // if (this.prepa[unit.id] !== undefined) {
        if (this.currentOrder != null) {
          prepOrders.push(unit)
        } else {
          noOrders.push(unit)
        }
      } else {
        // Regroupement des unités par ordre (même hash = même ordre)
        const orderHash = string_short(JSON.stringify(unit.or))
        if (!withOrders[orderHash]) {
          withOrders[orderHash] = []
        }
        withOrders[orderHash].push(unit)
      }
    }

    logMessage(
      'Found : noO=' +
        JSON.stringify(noOrders) +
        ' / prepO=' +
        JSON.stringify(prepOrders) +
        ' / wO=' +
        JSON.stringify(withOrders)
    )

    // Initialisation de la collection de cartouches
    // Création du cartouche pour les unités sans ordre ni préparation
    if (noOrders.length > 0) {
      logMessage('Generating ADLaplanete unit cartouches...')
      let noOrderHash = entitiesIdString(noOrders)
      this.cartouches[noOrderHash] = new CartNone(noOrderHash, noOrders)
      // -----------------------------------------------------------------
      // TODO : Clarifier si cette ligne est toujours nécessaire
      Mv.clear_path()
      // -----------------------------------------------------------------
    }

    // Création des cartouches pour les unités avec des ordres
    for (const [hash, units] of Object.entries(withOrders)) {
      logMessage('Generating BUSY unit cartouches...')

      const order = Or.order_from_server(units, units[0].or)

      logMessage('Ok on tente de gerer les ordres.')

      this.currentOrder = order

      Mv.set_path(this.currentOrder.path)

      let withOrderHash = entitiesIdString(units)
      logMessage(' HASH ===> ' + withOrderHash)

      this.cartouches[withOrderHash] = new CartOrder(withOrderHash, order, units)
    }

    // Création du cartouche pour les unités en préparation
    if (prepOrders.length > 0) {
      logMessage('Generating CurrentlyBriefing unit cartouches...')
      let prepOrderHash = entitiesIdString(prepOrders)
      this.cartouches[prepOrderHash] = new CartPrep(prepOrderHash, this.currentOrder, prepOrders)
    }
  }

  genCityCartouches(hash) {
    logMessage('Generating city cartouche...')
    this.cartouches[hash] = new CityCartouche(hash, this.selectedEntities[0])
  }
  /*--------------------------------------------------------------------------
		DRAWINGS
	--------------------------------------------------------------------------*/
  drawCartouches() {
    this.cartouches.forEach((cartouche) => cartouche.draw())
  }
  drawCartouchesInside() {
    logMessage('Draw only inside !')
    logMessage('(A1)==> Existing Carts before : ')
    this.existingCarts()
    this.genCartouches()
    logMessage('(A2)==> Existing Carts after : ')
    this.existingCarts()
    logMessage('Drawing now :')
    this.cartouches.forEach((cartouche, index) => {
      logMessage(`Classe du cartouche ${index} : ${cartouche.constructor.name}`)
      cartouche.drawInside()
      logMessage(`Cartouche ${index} dessiné avec succès.`)
    })
  }
  clearCartouches(cart_tag) {
    // Only clear the inside to avoid blinking.
    logMessage('Clearing cartouches !')
    this.currentOrder = null
    this.preparingOrder = false
    this.target = null
    logMessage('(1)==> Existing Carts before : ')
    this.existingCarts()
    this.drawCartouchesInside()
    logMessage('(2)==> Existing Carts after : ')
    this.existingCarts()
  }

  /*==============================================================================
██╗███╗   ██╗████████╗███████╗██████╗ ███████╗ █████╗  ██████╗███████╗
██║████╗  ██║╚══██╔══╝██╔════╝██╔══██╗██╔════╝██╔══██╗██╔════╝██╔════╝
██║██╔██╗ ██║   ██║   █████╗  ██████╔╝█████╗  ███████║██║     █████╗  
██║██║╚██╗██║   ██║   ██╔══╝  ██╔══██╗██╔══╝  ██╔══██║██║     ██╔══╝  
██║██║ ╚████║   ██║   ███████╗██║  ██║██║     ██║  ██║╚██████╗███████╗
╚═╝╚═╝  ╚═══╝   ╚═╝   ╚══════╝╚═╝  ╚═╝╚═╝     ╚═╝  ╚═╝ ╚═════╝╚══════╝
	
	Called from this module or other modules, by Se.xxxxx()

==============================================================================*/
  // ABOUT STATUS ------------------------------------------------------------
  currentPreparation() {
    // Check if there is currently an order that I prepare
    return this.preparingOrder
  }

  // ABOUT SELECTION ---------------------------------------------------------
  select(entities) {
    // allowLogs();
    this.deleteCartouches()
    this.selectedEntities = entities
    // Log des entités sélectionnées
    for (let entity of entities) {
      const className = getClassName(entity)
      logMessage(`Entity's type : ${className}`)
      Mv.add_select_by_id(className.toLowerCase(), entity.id)

      $('#selection').html(
        '<div id="selection_container"><div id="selection_display">Loading...</div></div>'
      )

      C.ask_data('GET_ORDER', entity.id, this.addSelectedInfo.bind(this))
    }
    // disableLogs();
  }
  removeEntity(entity) {
    // allowLogs();
    logMessage(`Removing one entity.`)
    this.selectedEntities = this.selectedEntities.filter((e) => e !== entity)
    // disableLogs();
  }
  isSelected(entity) {
    // allowLogs();
    const bool_value = this.selectedEntities.some((e) => e.id === entity.id)
    // disableLogs();
    return bool_value
  }
  deleteSelection() {
    // allowLogs();
    logMessage('Delete Selection')
    this.target = null
    this.currentOrder = null
    this.selectedEntities = []
    this.preparingOrder = false

    this.deleteCartouches()
    this.cartouches = []

    Mv.clear_select()
    // disableLogs();
  }

  // ABOUT ORDERS ------------------------------------------------------------
  cancelTarget() {
    // allowLogs();
    if (!this.currentPreparation()) {
      return false
    }
    if (!this.currentOrder.use_path) {
      return false
    }
    this.target = null
    Mv.set_path(this.currentOrder.path)
    // disableLogs();
  }
  // On defini un chemin entre la derniere etape connue et la souris :
  setTarget(target) {
    // allowLogs();
    logMessage('Define a target!')

    const currentTarget = target.stg()
    if (this.last_set == currentTarget) {
      return false
    }
    if (!this.currentPreparation()) {
      return false
    }
    if (!this.currentOrder.use_path) {
      return false
    }

    const curr_path = this.currentOrder.path
    this.last_set = currentTarget

    this.target = target // Utile pour le "durcissement"
    const tmp_path = Mv.find_path(this.currentOrder.last_path(), this.target)
    const path_to_show = curr_path.concat(tmp_path)
    // On doit faire comme si c'etait notre path pour afficher les calculs de duree
    this.currentOrder.set_path(path_to_show)
    Mv.set_path(path_to_show)

    this.drawCartouchesInside()
    // Sauf que l'on ne doit pas s'en "souvenir" pour la suite :
    this.currentOrder.set_path(curr_path)
    // disableLogs();
    return true
  }
  // On "durci" un chemin entre la derniere etape connue et le spot cliqué :
  setStep() {
    // allowLogs();
    if (!this.currentPreparation()) {
      return false
    }
    if (!this.currentOrder.use_path) {
      return false
    }
    if (!this.target) {
      return false
    }
    logMessage('Adding a step!')
    // Vérifier si le dernier chemin est différent de la cible
    const lastPath = this.currentOrder.last_path()
    if (lastPath.stg() !== this.target.stg()) {
      // Trouver un nouveau chemin et le concaténer au chemin actuel
      logMessage('asking for a path to : ' + this.target.stg() + ' ...')
      logMessage('asking for a path From : ' + lastPath.stg() + ' ...')
      const tmp_path = Mv.find_path(lastPath, this.target)
      this.currentOrder.add_to_path(tmp_path)
      // Mettre à jour le chemin affiché
      logMessage('Selection add path from set_step :')
      Mv.set_path(this.currentOrder.path)
    }
    // disableLogs();
  }
  cancelOrder(cart_tag) {
    // allowLogs();
    logMessage('Canceling order for cart ' + cart_tag)
    this.preparingOrder = false
    let units = this.cartouches[cart_tag].units
    for (const unit of units) {
      if (unit.hasOwnProperty('or')) {
        delete unit.or
      }
    }
    if (this.currentPreparation()) {
      logMessage('Canceling order we are preparing !')
      this.clearCartouches()
    } else {
      logMessage('Canceling order from the server !')
      const ids_stg = this.cartouches[cart_tag].allIds().join(',')
      const to_api = ids_stg
      C.ask_data('CANCEL_ORDER', to_api, this.clearCartouches.bind(this, cart_tag))
    }
    // disableLogs();
  }
  // FINAL :  ID-ORDER-DETAILS
  // or : 	ID1,ID2,ID3-ORDER-DETAILS
  validatePrepa(cart_tag) {
    // allowLogs();
    const ids_stg = this.cartouches[cart_tag].allIds().join(',')
    const cmd_order = this.cartouches[cart_tag].order.server_version()
    const to_api = ids_stg + '-' + cmd_order
    C.ask_data('SET_ORDER', to_api, this.addSelectedInfo.bind(this))
    this.preparingOrder = false
    Mv.clear_select()
    // disableLogs();
  }
  setPrepa(order_name, cart_tag) {
    // allowLogs();
    // On lance la preparation d'un ordre - Cela degage toutes les unites qui ne sont pas concernees
    this.preparingOrder = true
    let units = this.cartouches[cart_tag].units
    this.currentOrder = Or.order_from_gui(units, order_name)
    this.drawCartouchesInside()
    // disableLogs();
  }
  serverRefresh() {
    // allowLogs();
    // A thing to code.
    logMessage(
      'Seems Like something happend, a turn change or something, but I do not know exactly what its expecting of me'
    )
    // disableLogs();
  }

  /*==============================================================================
 ██████╗ ██╗   ██╗██╗
██╔════╝ ██║   ██║██║
██║  ███╗██║   ██║██║
██║   ██║██║   ██║██║
╚██████╔╝╚██████╔╝██║
 ╚═════╝  ╚═════╝ ╚═╝
==============================================================================*/
  initEvents() {
    // Key press to delete selection (Esc key)
    $(document).on('keyup keydown', function (e) {
      if (e.keyCode === 27) {
        Se.deleteSelection()
        return
      }
    })
    // Mouseover event to cancel target if not on map
    $(document).on('mouseover', function (e) {
      const id_cible = $(e.target).attr('id')
      if (id_cible && id_cible !== 'map' && Se) {
        Se.cancelTarget()
      }
    })
    // Click event to set a step of the path
    $(document).on('click', function () {
      if (Se) {
        Se.setStep()
      }
    })
  }
}

// PAS de clic droit sur Selection :
$('#selection').contextmenu(function () {
  return false
})

$('#selection').mousedown(function (e) {
  // CLIC GAUCHE ---------------------------------------------------------
  if (e.which === 1) {
    // Les allowLogs() et disableLogs() doivent se faire dans les points
    // D'entree Se.xxxx()

    const target = $(e.target) // Stocker le target pour éviter de le recalculer

    // Basculer sur le panneau détaillé pour la chose concernée
    if (target.hasClass('CI')) {
      const cityId = target.attr('id')
      if (cityId) Pl.details('city', cityId)
      // Pl.show('city', PanelCity.getContent(cityId));
    }
    if (target.hasClass('UI')) {
      const unitId = target.attr('id')
      if (unitId) Pl.details('unit', unitId)
      // Pl.show('unit', PanelCity.getContent(unitId));
    }

    // Ne choisir qu'une unité
    if (target.hasClass('US')) {
      const unitId = target.attr('id')
      if (unitId) Se.select([unitId])
    }

    // Préparer un ordre
    if (target.hasClass('O')) {
      const cartTag = target.parent().attr('id')?.slice(2) // Validation sécurisée
      if (cartTag) Se.setPrepa(target.attr('id'), cartTag)
    }

    // Valider un ordre
    if (target.hasClass('VALIDATE')) {
      const cartTag = target.parent().parent().attr('id')?.slice(2)
      if (cartTag) Se.validatePrepa(cartTag)
    }

    // Annuler un ordre en cours ou en préparation
    if (target.hasClass('CANCEL')) {
      const cartTag = target.parent().parent().attr('id')?.slice(2)
      logMessage('Canceling for cartTag:' + cartTag)
      Pp.confirmPopup({
        text: 'Want to cancel the order?',
        ok: function () {
          if (cartTag) Se.cancelOrder(cartTag)
        }
      })
    }
  }
})
