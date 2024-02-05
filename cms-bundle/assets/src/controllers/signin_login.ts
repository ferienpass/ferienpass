'use strict';

import {Controller} from '@hotwired/stimulus';
// @ts-ignore
import {Component, getComponent} from '@symfony/ux-live-component';

export default class extends Controller {
    static values = {
        requestToken: String,
    };

    static targets = [ "username", "password", "remember_me" ]

    declare readonly requestTokenValue?: string;
    declare readonly usernameTarget: HTMLInputElement;
    declare readonly passwordTarget: HTMLInputElement;

    declare component?: Component;

    async initialize() {
        if (!(this.element instanceof HTMLElement)) {
            return;
        }

        this.component = await getComponent(this.element);

        this.component.on('render:finished', (component: Component) => {

        });
    }

    submit(event: SubmitEvent) {
       fetch('/check_login', { method: 'POST', headers: {'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest'}, body: JSON.stringify({username: this.usernameTarget.value, password: this.passwordTarget.value, REQUEST_TOKEN: this.requestTokenValue}) })
            .then(response => response.json())
            //.then(json => message = json.message)
            //.then(message => { if(!message) authError = true })
            .catch(() => { window.location.reload() })
            //.finally(() => { isLoading = false; })
    }
}
