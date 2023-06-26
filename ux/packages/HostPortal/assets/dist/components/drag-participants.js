"use strict";
var __createBinding = (this && this.__createBinding) || (Object.create ? (function(o, m, k, k2) {
    if (k2 === undefined) k2 = k;
    Object.defineProperty(o, k2, { enumerable: true, get: function() { return m[k]; } });
}) : (function(o, m, k, k2) {
    if (k2 === undefined) k2 = k;
    o[k2] = m[k];
}));
var __setModuleDefault = (this && this.__setModuleDefault) || (Object.create ? (function(o, v) {
    Object.defineProperty(o, "default", { enumerable: true, value: v });
}) : function(o, v) {
    o["default"] = v;
});
var __importStar = (this && this.__importStar) || function (mod) {
    if (mod && mod.__esModule) return mod;
    var result = {};
    if (mod != null) for (var k in mod) if (k !== "default" && Object.prototype.hasOwnProperty.call(mod, k)) __createBinding(result, mod, k);
    __setModuleDefault(result, mod);
    return result;
};
Object.defineProperty(exports, "__esModule", { value: true });
const sortablejs_1 = __importStar(require("sortablejs"));
sortablejs_1.default.mount(new sortablejs_1.MultiDrag());
exports.default = (count, autoAssign) => {
    return {
        count: count,
        autoAssign: autoAssign,
        init() {
            sortablejs_1.default.create(this.$refs.list, {
                group: 'assign',
                ghostClass: 'bg-yellow-50',
                selectedClass: 'bg-blue-50',
                multiDrag: true,
                onAdd: this.assignAttendanceOnAdd(event, autoAssign),
                onUpdate: this.assignAttendanceOnUpdate(event, autoAssign),
                onSort: () => this.count = this.$root.querySelectorAll('ul > li').length
            });
        },
        assignAttendanceOnAdd() {
            return (evt, reload) => {
                fetch(`/admin/api/attendance/${evt.item.dataset.attendanceId}/sort`, {
                    method: 'POST',
                    body: new URLSearchParams(`newStatus=${evt.to.dataset.attendanceStatus}&newIndex=${evt.newIndex}`)
                }).then(response => {
                    if (!response.ok)
                        alert('Ein Fehler ist aufgetreten! Bitte Seite neu laden.');
                });
                if (reload) {
                    window.location.reload();
                }
            };
        },
        assignAttendanceOnUpdate() {
            return (evt, reload) => {
                fetch(`/admin/api/attendance/${evt.item.dataset.attendanceId}/sort`, {
                    method: 'POST',
                    body: new URLSearchParams(`newIndex=${evt.newIndex}`)
                }).then(response => {
                    if (!response.ok)
                        alert('Ein Fehler ist aufgetreten! Bitte Seite neu laden.');
                });
                if (reload) {
                    window.location.reload();
                }
            };
        }
    };
};
