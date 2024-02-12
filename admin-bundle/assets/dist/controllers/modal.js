'use strict';
import {Controller} from '@hotwired/stimulus';
// @ts-ignore
import {enter, leave} from "el-transition";
//import {Component, getComponent} from '@symfony/ux-live-component';
export default class default_1 extends Controller {
    show() {
        this.element.classList.remove("hidden");
        enter(this.backdropTarget);
        enter(this.modalTarget);
    }
    hide() {
        console.log(this.element);
        Promise.all([
            leave(this.backdropTarget),
            leave(this.modalTarget),
        ]).then(() => {
            this.element.classList.add("hidden");
        });
    }
}
default_1.values = {};
default_1.targets = ["backdrop", "modal"];
