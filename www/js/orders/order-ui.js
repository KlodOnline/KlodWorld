export class OrderUI {  
    static showConfirmation(message, onConfirm, onCancel) {  
        Pp.confirmPopup({  
            text: message,  
            ok: onConfirm,  
            cancel: onCancel  
        });  
    }  
      
    static showError(message) {  
        // Affichage d'erreur (à adapter selon votre système UI)  
        console.error('Order Error:', message);  
    }  
      
    static showSuccess(message) {  
        // Affichage de succès  
        console.log('Order Success:', message);  
    }  
      
    static renderOrderPreparation(order) {  
        return '<div class="">' + order.name + '<br/>' +  
               '<button class="VALIDATE">OK</button>' +  
               '<button class="CANCEL">CANCEL</button></div>';  
    }  
}