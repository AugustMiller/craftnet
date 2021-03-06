<template>
    <div>
        <h4>Location</h4>

        <p class="text-grey-darker">Location and sales contact information for potential clients.</p>

        <partner-location
            v-for="(location, index) in draftLocations"
            :location="location"
            :key="index"
            :index="index"
            :edit-index="editIndex"
            :request-pending="requestPending"
            :errors="errors[index]"
            @cancel="onCancel"
            @delete="onDelete"
            @edit="onEdit"
            @save="onSave"
        ></partner-location>

        <!-- Multiple locations not currently enabled -->
        <div v-if="draftLocations.length === 0" class="pl-4">
            <button class="btn btn-secondary btn-sm" @click="onAddLocationClick"><i class="fa fa-plus"></i> Add a Location</button>
        </div>
    </div>
</template>

<script>
    import helpers from '../mixins/helpers'
    import PartnerLocation from './PartnerLocation'

    export default {
        props: ['partner'],

        mixins: [helpers],

        components: {
            PartnerLocation,
        },

        data() {
            return {
                errors: [],
                draftLocations: [],
                draftLocationProps: [
                    'id',
                    'title',
                    'addressLine1',
                    'addressLine2',
                    'city',
                    'state',
                    'zip',
                    'country',
                    'phone',
                    'email',
                ],
                editIndex: null,
                requestPending: false,
            }
        },

        methods: {
            onAddLocationClick() {
                this.draftLocations.push({
                    id: 'new',
                    title: '',
                    addressLine1: '',
                    addressLine2: '',
                    city: '',
                    state: '',
                    zip: '',
                    country: '',
                    phone: '',
                    email: '',
                })
            },

            cloneLocations(locations) {
                let clone = []

                for (let i = 0; i < locations.length; i++) {
                    const location = this.partner.locations[i]
                    clone.push(this.simpleClone(location, this.draftLocationProps))
                }

                return clone
            },

            onCancel() {
                // reset
                this.setDraftLocations()
                this.editIndex = null
            },

            onDelete(index) {
                if (this.draftLocations.length === 1) {
                    this.$root.displayError('Must have at least one location');
                    return;
                }

                // we can't splice `draftLocations` or the modal for the
                // spliced out location will disappear
                let locations = this.cloneLocations(this.draftLocations)
                locations.splice(index, 1)
                this.save(locations)
            },

            onEdit(index) {
                this.errors = []
                this.editIndex = index
            },

            onSave() {
                this.save(this.draftLocations);
            },

            save(locations) {
                this.requestPending = true

                this.$store.dispatch('patchPartnerLocations', locations)
                    .then(response => {
                        this.requestPending = false

                        if (!response.data.success) {
                            this.errors = response.data.errors.locations
                            this.$root.displayError('Validation errors')
                        } else {
                            this.setDraftLocations();
                            this.$root.displayNotice('Updated')
                            this.editIndex = null
                        }
                    })
                    .catch(errorMessage => {
                        this.$root.displayError(errorMessage)
                        this.requestPending = false
                    })
            },

            setDraftLocations() {
                this.draftLocations = this.cloneLocations(this.partner.locations)
            }
        },

        mounted() {
            this.setDraftLocations()
        },
    }
</script>
