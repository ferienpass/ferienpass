'use strict';
var __awaiter = (this && this.__awaiter) || function (thisArg, _arguments, P, generator) {
    function adopt(value) { return value instanceof P ? value : new P(function (resolve) { resolve(value); }); }
    return new (P || (P = Promise))(function (resolve, reject) {
        function fulfilled(value) { try { step(generator.next(value)); } catch (e) { reject(e); } }
        function rejected(value) { try { step(generator["throw"](value)); } catch (e) { reject(e); } }
        function step(result) { result.done ? resolve(result.value) : adopt(result.value).then(fulfilled, rejected); }
        step((generator = generator.apply(thisArg, _arguments || [])).next());
    });
};
import {Controller} from '@hotwired/stimulus';
import Sortable, {MultiDrag} from "sortablejs";
// @ts-ignore
import {getComponent} from '@symfony/ux-live-component';

export default class default_1 extends Controller {
    initialize() {
        return __awaiter(this, void 0, void 0, function* () {
            this.component = yield getComponent(this.element);
            Sortable.mount(new MultiDrag());
            [this.confirmedColumnTarget, this.waitlistedColumnTarget, this.waitingColumnTarget, this.withdrawnColumnTarget].forEach((column) => {
                const list = column.querySelector('ul[data-attendance-status]');
                if (!(list instanceof HTMLElement)) {
                    return;
                }
                Sortable.create(list, {
                    group: 'assign',
                    ghostClass: 'bg-yellow-50',
                    selectedClass: 'bg-blue-50',
                    multiDrag: true,
                    onAdd: (event) => this.component.emit('statusChanged', {
                        attendance: event.item.dataset.attendanceId,
                        newStatus: event.to.dataset.attendanceStatus,
                        newIndex: event.newIndex
                    }),
                    onUpdate: (event) => this.component.emit('indexUpdated', {
                        attendance: event.item.dataset.attendanceId,
                        newIndex: event.newIndex
                    }),
                    onSort: () => this.countValue = column.querySelectorAll('ul > li').length
                });
            });
        });
    }
}
default_1.values = {
    count: Number,
};
default_1.targets = ["confirmedColumn", "waitlistedColumn", "waitingColumn", "withdrawnColumn"];
