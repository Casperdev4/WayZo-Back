import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
  static targets = ['texte'];

  partir() {
    this.texteTarget.innerHTML = 'Statut actuel : <strong>En route vers le client 🚗</strong>';
  }

  recuperer() {
    this.texteTarget.innerHTML = 'Statut actuel : <strong>Client à bord ✅</strong>';
  }

  terminer() {
    this.texteTarget.innerHTML = 'Statut actuel : <strong>Course terminée 🏁</strong>';
  }
}

