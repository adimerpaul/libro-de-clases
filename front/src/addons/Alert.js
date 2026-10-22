import {Dialog, Notify} from 'quasar'
import Icon from "../components/Icon.vue";

export class Alert{
  static warning(message, subTitle = '') {
    Notify.create({
      progress: true,
      color: 'white',
      textColor: 'black',
      position: 'top',
      message,
      caption: subTitle,
      timeout: 3000,
      icon: 'fas fa-triangle-exclamation',
      iconColor: 'warning',
      actions: [
        { icon: 'fas fa-xmark', color: 'black', round: true, size: 'xs' }
      ],
      progressClass: 'bg.jpg-warning',
      classes: 'bg.jpg-white text-black text-bold left-yellow-border'
    });
  }
  static info(message, subTitle = '') {
    Notify.create({
      progress: true,
      color: 'white',
      textColor: 'black',
      position: 'top',
      message,
      caption: subTitle,
      timeout: 3000,
      icon: 'fas fa-circle-info',
      iconColor: 'info',
      actions: [
        { icon: 'fas fa-xmark', color: 'black', round: true, size: 'xs' }
      ],
      progressClass: 'bg.jpg-info',
      classes: 'bg.jpg-white text-black text-bold left-blue-border'
    });
  }
  static success(message,subTitle = '') {
    Notify.create({
      progress: true,
      color: 'white',
      textColor: 'black',
      position: 'top',
      message,
      caption: subTitle,
      timeout: 3000,
      icon: 'fas fa-circle-check',
      iconColor: 'positive',
      actions: [
        { icon: 'fas fa-xmark', color: 'black', round: true, size: 'xs' }
      ],
      progressClass: 'bg.jpg-positive',
      classes: 'bg.jpg-white text-black text-bold left-green-border'
    });
  }
  static error(message,subTitle = '') {
    Notify.create({
      progress: true,
      color: 'white',
      textColor: 'black',
      position: 'top',
      message,
      caption: subTitle,
      timeout: 3000,
      icon: 'fas fa-circle-xmark',
      iconColor: 'negative',
      actions: [
        { icon: 'fas fa-xmark', color: 'black', round: true, size: 'xs' }
      ],
      progressClass: 'bg.jpg-negative',
      classes: 'bg.jpg-white text-black text-bold left-red-border'
    });
  }
  static dialog (title, message) {
    // return Dialog.create({
    //   title: 'Confirmación',
    //   component: undefined,
    //   message,
    //   // position: 'top',
    //   color: 'positive',
    //   ok: {
    //     label: 'Aceptar',
    //     color: 'positive'
    //   },
    //   cancel: {
    //     label: 'Cancelar',
    //     color: 'negative'
    //   },
    // })
    // this.$q.dialog({
    //   title: 'Anular Pago',
    //   message: '¿Está seguro de anular el pago?',
    //   cancel: true,
    //   component: Icon,
    // })

    return Dialog.create({
      component: Icon,
      componentProps: {
        title: title,
        message: message,
        icon: 'fas fa-triangle-exclamation',
        color: 'negative',
      }
    })
  }
  static confirm (message) {
    return Dialog.create({
      title: 'Confirmación',
      message,
      // position: 'top',
      color: 'positive',
      ok: {
        label: 'Aceptar',
        color: 'positive'
      },
      cancel: {
        label: 'Cancelar',
        color: 'negative'
      },
    })
  }
  static dialogPrompt (message) {
    return Dialog.create({
      title: 'Confirmación',
      message,
      // position: 'top',
      color: 'positive',
      ok: {
        label: 'Aceptar',
        color: 'positive'
      },
      cancel: {
        label: 'Cancelar',
        color: 'negative'
      },
      prompt: {
        model: '',
        type: 'text',
        label: 'Ingrese el texto',
        // required: true,
        // rules: [(val) => val !== '']
      }
    })
  }

}
