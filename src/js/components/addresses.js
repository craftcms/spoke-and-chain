window.addresses = function() {
    return {
        modelId: null,
        countryId: null,
        stateId: null,
        modelName: null,
        stateSelectId: null,
        stateTextId: null,
        showStateSelect: false,
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
        }
    };
};