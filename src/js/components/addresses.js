window.addresses = function() {
    return {
        modelId: null,
        countryId: null,
        stateId: null,
        modelName: null,
        stateSelectId: null,
        stateTextId: null,
        showStateSelect: false,
        errors: {},

        allStates() {
            return window.addressStates;
        },
        states() {
            if (this.countryId && Object.keys(this.allStates()).indexOf(this.countryId) >= 0) {
                let states = [];
                Object.keys(this.allStates()[this.countryId]).forEach(key => {
                    states.push({
                        id: key,
                        name: this.allStates()[this.countryId][key]
                    });
                });

                return states;
            }

            return [];
        },
        stateSelected(state) {
            return state && state.id == this.stateId;
        },
        toggleStates() {

            if (this.states().length) {
                this.stateSelectId = this.modelName;
                if (this.modelId) {
                    this.stateSelectId = this.stateSelectId + '-' + this.modelId;
                }
                this.stateSelectId = this.stateSelectId + '-state';

                this.stateTextId = '';
                this.showStateSelect = true;
            } else {
                this.stateSelectId = '';
                this.stateTextId = this.modelName;
                if (this.modelId) {
                    this.stateTextId = this.stateTextId + '-' + this.modelId;
                }
                this.stateTextId = this.stateTextId + '-state';
                this.showStateSelect = false;
            }

        },
        onChange(ev) {
            this.countryId = ev.target.value;
            this.toggleStates();
        },
        getErrors(key) {
            if (!Object.keys(this.errors).length || Object.keys(this.errors).indexOf(key) === -1) {
                return false;
            }

            return this.errors[key];
        },
        hasErrors(key) {
            let errors = this.getErrors(key);
            return errors && errors.length;
        },
        updateErrors(errors) {
            this.errors = errors;
        }
    };
};