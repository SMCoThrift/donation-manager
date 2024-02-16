const PUMD = {
    userPortal:{
        notification:{
            notificationArea:null,
            show:  ({message='', title='', type='info'}) => {
                let notificationTitle = this.notificationArea.querySelector('.elementor-alert-title');
                if(notificationTitle){
                    notificationTitle.textContent = title;
                }
                let description = this.notificationArea.querySelector('.elementor-alert-description');
                if(description){
                    description.textContent = message;
                }

                let notificationType = this.notificationArea.querySelector('.elementor-alert');
                let classes = notificationType.classList;
                for (let i = 0; i < classes.length; i++) {
                    if(classes[i].startsWith('elementor-alert-')){
                        notificationType.classList.remove(classes[i]);
                    }
                }
                notificationType.classList.add('elementor-alert-'+type);
                this.notificationArea.classList.remove('dce-visibility-element-hidden');
                this.notificationArea.style.display = 'block';
            },
            init: () => {
                this.notificationArea =  document.getElementById('userportal-notification-area');
                if(this.notificationArea){
                    document.body.addEventListener("showUserportalNotification", function (evt) {
                        let message = evt.detail.message;
                        let title = evt.detail.title;
                        let type = evt.detail.type;
                        PUMD.userPortal.notification.show({message, title, type});
                    })
                }
            }
        }
    }
}
document.addEventListener('DOMContentLoaded',PUMD.userPortal.notification.init);
