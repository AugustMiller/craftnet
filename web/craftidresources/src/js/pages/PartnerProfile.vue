<template>
	<div>
		<h1>Partner Profile</h1>

        <div v-if="loadState == LOADING" class="text-center">
            <div class="spinner big mt-8"></div>
        </div>

        <p v-if="loadState == LOAD_ERROR">Error: {{ loadError }}</p>

        <div v-if="loadState == LOADED">
            <partner-completion :partner="partner"></partner-completion>
            <partner-info :partner="partner"></partner-info>
            <partner-locations :partner="partner"></partner-locations>
            <partner-projects :partner="partner"></partner-projects>
        </div>
	</div>
</template>

<script>
    import {mapState} from 'vuex'
    import PartnerCompletion from '../components/PartnerCompletion'
    import PartnerInfo from '../components/PartnerInfo'
    import PartnerLocations from '../components/PartnerLocations'
    import PartnerProjects from '../components/PartnerProjects'

    export default {

        data() {
            return {
                LOADED: 'loaded',
                LOADING: 'loading',
                LOAD_ERROR: 'loadError',

                loadState: 'loading',
                loadError: ''
            }
        },

        components: {
            PartnerCompletion,
            PartnerInfo,
            PartnerLocations,
            PartnerProjects
        },

        computed: {
            ...mapState({
                partner: state => state.partner.partner,
            }),
        },

        mounted() {
            this.$store.dispatch('initPartner')
                .then(() => {
                    this.loadState = this.LOADED
                })
                .catch((response) => {
                    this.loadState = this.LOAD_ERROR
                    this.loadError = response.data.error || 'Couldn’t load partner profile'
                })
        }
    }
</script>
