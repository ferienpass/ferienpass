"use strict";
var __importDefault = (this && this.__importDefault) || function (mod) {
    return (mod && mod.__esModule) ? mod : { "default": mod };
};
Object.defineProperty(exports, "__esModule", { value: true });
const alpinejs_1 = __importDefault(require("alpinejs"));
require("./components/OfferDatepickerElement");
//import DashboardGantt from './components/gantt.js'
const heatmap_js_1 = __importDefault(require("./components/heatmap.js"));
const drag_participants_js_1 = __importDefault(require("./components/drag-participants.js"));
window.Alpine = alpinejs_1.default;
document.addEventListener('alpine:init', () => {
    //Alpine.data('DashboardGantt', DashboardGantt)
    alpinejs_1.default.data('DashboardHeatmap', heatmap_js_1.default);
    alpinejs_1.default.data('DragParticipants', drag_participants_js_1.default);
});
alpinejs_1.default.start();
