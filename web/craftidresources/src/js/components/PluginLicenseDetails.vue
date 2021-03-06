<template>
	<div class="card mb-3">
		<div class="card-body">
			<h4 class="mb-4">License Details</h4>
			<template v-if="license">
				<div class="md:flex -mx-4">
					<div class="md:w-1/2 px-4">
						<dl>
							<template v-if="license.plugin">
								<dt>Plugin</dt>
								<dd>{{ license.plugin.name }}</dd>
							</template>

							<dt>License Key</dt>
							<dd><code>{{ license.key|formatPluginLicense }}</code></dd>

							<dt>Craft License</dt>
							<dd>
								<template v-if="license.cmsLicense">
									<p>
										<code>
											<router-link v-if="license.cmsLicense.key" :to="'/account/licenses/cms/'+license.cmsLicenseId">{{ license.cmsLicense.key.substr(0, 10) }}</router-link>
											<template v-else>{{ license.cmsLicense.shortKey }}</template>
										</code>
										<span v-if="license.cmsLicense.edition" class="text-secondary">(Craft {{ license.cmsLicense.edition }})</span>
									</p>
									<div class="buttons">
										<button @click="detachCmsLicense()" type="button" class="btn btn-secondary btn-sm">
											Detach from this Craft license
										</button>
										<div class="spinner" v-if="detaching"></div>
									</div>
								</template>
								<template v-else>
									<span class="text-secondary">Not attached to a CMS license.</span>
                                    <a v-if="originalCmsLicenseId" @click.prevent="reattachCmsLicense()" href="#">Undo</a>
								</template>
							</dd>
						</dl>
					</div>
					<div class="md:w-1/2 px-4">
						<dl>
							<dt>Email</dt>
							<dd>{{ license.email }}</dd>

							<template v-if="enableRenewalFeatures">
								<dt>Update Period</dt>
								<dd>2017/05/11 to 2018/05/11</dd>

								<dt>Auto Renew</dt>
								<dd>
									<lightswitch-input @input="saveAutoRenew()" v-model="licenseDraft.autoRenew"></lightswitch-input>
								</dd>
							</template>

							<dt>Created</dt>
							<dd>{{ license.dateCreated.date|moment("L") }}</dd>

							<dt>Notes</dt>
							<dd>
								<template v-if="!notesEditing">
									<p>{{ license.notes }}</p>

									<div class="buttons">
										<button @click="notesEditing = true" type="button" class="btn btn-secondary btn-sm">
											<i class="fas fa-pencil-alt"></i>
											Edit
										</button>
									</div>
								</template>

								<form v-if="notesEditing" @submit.prevent="saveNotes()">
									<textarea-field id="notes" v-model="licenseDraft.notes" @input="notesChange"></textarea-field>
									<input type="submit" class="btn btn-primary" value="Save" :class="{disabled: !notesValidates}" :disabled="!notesValidates" />
									<input @click="cancelEditNotes()" type="button" class="btn btn-secondary" value="Cancel" />
									<div class="spinner" v-if="notesLoading"></div>
								</form>
							</dd>
						</dl>
					</div>
				</div>
			</template>
		</div>
	</div>
</template>

<script>
    import {mapState} from 'vuex'
    import LightswitchInput from '../components/inputs/LightswitchInput'
    import TextareaField from '../components/fields/TextareaField'

    export default {

        props: ['license', 'type'],

        data() {
            return {
                errors: {},
                licenseDraft: {},
                originalCmsLicenseId: this.license.cmsLicenseId,
                originalCmsLicense: this.license.cmsLicense,
                detaching: false,
                reattaching: false,
                notesEditing: false,
                notesLoading: false,
                notesValidates: false,
            }
        },

        components: {
            LightswitchInput,
            TextareaField,
        },

        computed: {

            ...mapState({
                enableRenewalFeatures: state => state.craftId.enableRenewalFeatures,
            }),

        },

        methods: {

            /**
             * Detach the Craft license.
             */
            detachCmsLicense() {
                this.detaching = true;
                this.licenseDraft.cmsLicenseId = null;
                this.licenseDraft.cmsLicense = null;

                this.savePluginLicense(() => {
                    this.detaching = false;
                }, () => {
                    this.detaching = false;
                });
            },

            /**
             * Reattach the Craft license.
             */
            reattachCmsLicense() {
                this.reattaching = true;
                this.licenseDraft.cmsLicenseId = this.originalCmsLicenseId;
                this.licenseDraft.cmsLicense = this.originalCmsLicense;

                this.savePluginLicense(() => {
                    this.reattaching = false;
                }, () => {
                    this.reattaching = false;
                });
            },

            /**
             * Can save
             */
            canSave() {
                if (this.license.notes !== this.licenseDraft.notes) {
                    return true;
                }

                return false;
            },

            /**
             * Save notes.
             */
            saveNotes() {
                this.notesLoading = true;

                this.savePluginLicense(() => {
                    this.notesLoading = false;
                    this.notesEditing = false;
                }, () => {
                    this.notesLoading = false;
                });
            },

            /**
             * Cancel edit notes.
             */
            cancelEditNotes() {
                this.licenseDraft.notes = this.license.notes;
                this.notesEditing = false;
                this.notesValidates = false;
            },

            /**
             * Notes change.
             */
            notesChange() {
                this.notesValidates = false;

                if(this.licenseDraft.notes !== this.license.notes) {
                    this.notesValidates = true;
                }
            },

            /**
             * Save plugin license.
             *
             * @param cb
             * @param cbError
             */
            savePluginLicense(cb, cbError) {
                this.$store.dispatch('savePluginLicense', {
                    pluginHandle: this.license.plugin.handle,
                    key: this.license.key,
                    cmsLicenseId: this.licenseDraft.cmsLicenseId,
                    cmsLicense: this.licenseDraft.cmsLicense,
                    notes: this.licenseDraft.notes,
                }).then(data => {
                    cb();
                    this.$root.displayNotice('License saved.');
                }).catch(response => {
                    cbError();
                    const errorMessage = response.data && response.data.error ? response.data.error : 'Couldn’t save license.'
                    this.$root.displayError(errorMessage)
                });
            },

            /**
             * Save auto renew
             */
            saveAutoRenew() {
                this.$store.dispatch('savePluginLicense', {
                    id: this.license.id,
                    type: this.type,
                    autoRenew: (this.licenseDraft.autoRenew ? 1 : 0),
                }).then((data) => {
                    if (this.licenseDraft.autoRenew) {
                        this.$root.displayNotice('Auto renew enabled.');
                    } else {
                        this.$root.displayNotice('Auto renew disabled.');
                    }

                }).catch((data) => {
                    this.$root.displayError('Couldn’t save license.');
                    this.errors = data.errors;
                });
            },

        },

        mounted() {
            this.licenseDraft = {
                cmsLicenseId: this.license.cmsLicenseId,
                cmsLicense: this.license.cmsLicense,
                autoRenew: (this.license.autoRenew == 1 ? true : false),
                notes: this.license.notes,
            };
        }

    }
</script>
