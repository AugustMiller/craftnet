<template>

    <div v-if="enableRenewalFeatures" class="alert alert-info" role="alert">
        5 of your licenses are about to expire. <router-link to="/account/licenses/renew">Renew them now</router-link> and get a discount.
    </div>

</template>


<script>
    import {mapState} from 'vuex'

    export default {

        computed: {

            ...mapState({
                enableRenewalFeatures: state => state.craftId.enableRenewalFeatures,
            }),

        },

    }
</script>
