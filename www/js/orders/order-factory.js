export class OrderFactory {  
    static constructors = {  
        MOVE: OrderMove,  
        BUILD_CITY: OrderBuildCity,  
        BUILD_ROAD: OrderBuildRoad,  
        MOVE_ROAD: OrderMoveRoad,  
        CHOP_WOOD: OrderChopWood,
        RECRUIT: OrderRecruit,
        BUILDING: OrderBuilding
    };  
      
    static createFromServer(units, orderData) {  
        if (!orderData || !orderData.name) {  
            logMessage('OrderFactory: Invalid order data from server');  
            return null;  
        }  
          
        const OrderClass = this.constructors[orderData.name];  
        if (!OrderClass) {  
            logMessage('OrderFactory: Unknown order type: ' + orderData.name);  
            return null;  
        }  
          
        const order = new OrderClass();  
        order.generate(units, orderData);  
        order.log_info();  
        return order;  
    }  
      
    static createFromGUI(units, orderName) {  
        if (!orderName) {  
            logMessage('OrderFactory: No order name provided');  
            return null;  
        }  
          
        const OrderClass = this.constructors[orderName];  
        if (!OrderClass) {  
            logMessage('OrderFactory: Unknown order type: ' + orderName);  
            return null;  
        }  
          
        const orderData = { name: orderName, data: {} };  
        const order = new OrderClass();  
        order.generate(units, orderData);  
        order.log_info();  
        return order;  
    }  
      
    // Méthode pour ajouter de nouveaux types d'ordres dynamiquement  
    static registerOrderType(name, constructor) {  
        this.constructors[name] = constructor;  
    }  
      
    // Méthode pour obtenir la liste des ordres disponibles  
    static getAvailableOrderTypes() {  
        return Object.keys(this.constructors);  
    }  
}