export class OrderAPI {  
    static sendOrder(order, entityId, orderDetails = '') {  
        // Format des données selon le protocole existant  
        const postData = {  
            M: [entityId, order.name, orderDetails].join('-')  
        };  
          
        logMessage('OrderSender: Sending order ' + order.name + ' for entity ' + entityId);  
          
        return new Promise((resolve, reject) => {  
            C.ask_data('SET_ORDER', postData.M, (response) => {  
                if (response && !response.nope) {  
                    OrderUI.showSuccess('Order sent successfully');  
                    resolve(response);  
                } else {  
                    const error = 'Failed to send order: Server rejected request';  
                    OrderUI.showError(error);  
                    reject(new Error(error));  
                }  
            });  
        });  
    }  
      
    static cancelOrder(entityId) {  
        logMessage('OrderSender: Canceling order for entity ' + entityId);  
          
        return new Promise((resolve, reject) => {  
            C.ask_data('CANCEL_ORDER', entityId.toString(), (response) => {  
                if (response && !response.nope) {  
                    OrderUI.showSuccess('Order canceled successfully');  
                    resolve(response);  
                } else {  
                    const error = 'Failed to cancel order';  
                    OrderUI.showError(error);  
                    reject(new Error(error));  
                }  
            });  
        });  
    }  
      
    static getOrder(entityId) {  
        logMessage('OrderSender: Getting order for entity ' + entityId);  
          
        return new Promise((resolve, reject) => {  
            C.ask_data('GET_ORDER', entityId.toString(), (response) => {  
                if (response && !response.nope) {  
                    resolve(response);  
                } else {  
                    const error = 'Failed to get order';  
                    OrderUI.showError(error);  
                    reject(new Error(error));  
                }  
            });  
        });  
    }  
      
    // Méthode utilitaire pour formater les données d'ordre complexes  
    static formatOrderData(order) {  
        if (order.name === 'MOVE' && order.path) {  
            // Format spécial pour les ordres de mouvement avec chemin  
            const pathString = order.path.slice(1).map(step => step.stg()).join('_');  
            return order.name + '-' + pathString;  
        }  
          
        return order.server_version();  
    }  
}