window.addresses = function() {
    return {
        modelId: null,
        countryCode: null,
        administrativeArea: null,
        modelName: null,
        stateSelectId: null,
        stateTextId: null,
        showStateSelect: false,
        errors: {},

        allStates() {
            return window.addressStates;
        },
        states() {
            if (this.countryCode && this.allStates()[this.countryCode]) {
                let states = [];

                Object.keys(this.allStates()[this.countryCode]).forEach((key) => {
                    states.push({
                        id: key,
                        name: this.allStates()[this.countryCode][key],
                    });
                });

                return states;
            }

            return [];
        },
        stateSelected(state) {
            return state && state.id == this.administrativeArea;
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
            this.countryCode = ev.target.value;
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