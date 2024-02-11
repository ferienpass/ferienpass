'use strict';

import {Controller} from '@hotwired/stimulus';
import Sortable, {MultiDrag} from "sortablejs";
// @ts-ignore
import {Component, getComponent} from '@symfony/ux-live-component';

export default class extends Controller {
    static values = {
        count: Number,
    };

    static targets = ["confirmedColumn", "waitlistedColumn", "waitingColumn", "withdrawnColumn"]

    declare countValue?: Number;
    declare readonly confirmedColumnTarget: HTMLElement;
    declare readonly waitlistedColumnTarget: HTMLElement;
    declare readonly waitingColumnTarget: HTMLElement;
    declare readonly withdrawnColumnTarget: HTMLElement;

    declare component?: Component;

    async initialize() {
        this.component = await getComponent(this.element);
        Sortable.mount(new MultiDrag());

        [this.confirmedColumnTarget, this.waitlistedColumnTarget, this.waitingColumnTarget, this.withdrawnColumnTarget].forEach((column) => {
            const list = column.querySelector('ul[data-attendance-status]')
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
            })
        });
    }
}
