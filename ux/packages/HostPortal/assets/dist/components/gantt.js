"use strict";
var __importDefault = (this && this.__importDefault) || function (mod) {
    return (mod && mod.__esModule) ? mod : { "default": mod };
};
Object.defineProperty(exports, "__esModule", { value: true });
const frappe_gantt_1 = __importDefault(require("frappe-gantt"));
require('./gantt.css');
exports.default = (tasks) => {
    return {
        gantt: null,
        viewMode: 'Month',
        init() {
            this.gantt = new frappe_gantt_1.default(this.$refs.gantt, tasks, {
                view_mode: this.viewMode, custom_popup_html: function (task) {
                    return `
<div class="px-4 py-2">
<h5 class="text-white text-sm font-medium whitespace-nowrap">${task.name}
<a class="text-white font-normal text-xs underline" href="${task.editLink.href}" title="${task.editLink.title}">${task.editLink.link}</a>
</h5>
<p class="mt-1 text-gray-100 leading-4 text-sm">${task.description}</p>
</div>
`;
                }
            });
            this.$watch('viewMode', value => gantt.change_view_mode(value));
        }
    };
};
