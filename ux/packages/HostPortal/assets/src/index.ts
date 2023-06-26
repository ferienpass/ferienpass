import Alpine from 'alpinejs'

import './components/OfferDatepickerElement';

//import DashboardGantt from './components/gantt.js'
import DashboardHeatmap from './components/heatmap.js'
import DragParticipants from './components/drag-participants.js';

window.Alpine = Alpine

document.addEventListener('alpine:init', () => {

    //Alpine.data('DashboardGantt', DashboardGantt)
    Alpine.data('DashboardHeatmap', DashboardHeatmap)
    Alpine.data('DragParticipants', DragParticipants)

});

Alpine.start()
