'use strict';
import {Controller} from '@hotwired/stimulus';
// import * as Gantt from 'frappe-gantt';
// interface CustomTask extends Gantt.Task {
//     description: string;
//     editLink: string;
//     editTitle: string;
//     editHref: string;
// }
export default class default_1 extends Controller {
    connect() {
        //         const tasks = this.tasksValue.map(item => item as CustomTask);
        //
        //         new Gantt.default(this.element as HTMLElement, tasks, {
        //             view_mode: 'Month',
        //             // @ts-ignore
        //             custom_popup_html: (task: CustomTask): string => `
        // <div class="px-4 py-2">
        // <h5 class="text-white text-sm font-medium whitespace-nowrap">${task.name}
        // <a class="text-white font-normal text-xs underline" href="${task.editLink}" title="${task.editTitle}">${task.editHref}</a>
        // </h5>
        // <p class="mt-1 text-gray-100 leading-4 text-sm">${task.description}</p>
        // </div>
        // `
        //         });
    }
}
default_1.values = {
    tasks: Array
};
