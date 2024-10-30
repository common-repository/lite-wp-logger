export default {
    template: `
<nav class="sidebar sidebar-offcanvas" id="sidebar">
    <ul class="nav">
        <li class="nav-item nav-category main-sidebar-item">
            <img :src="pluginUrl + 'assets/admin/img/logo.svg'" alt="logo">
            <span><span style="color: #34B1AA;">WP</span> Logger</span>
        </li>
        <li :class="{ 'nav-item': true, active: routeName === 'reports' }">
            <router-link class="nav-link" :to="{ name: 'reports' }">
                <i class="menu-icon mdi mdi-access-point"></i>
                <span class="menu-title">{{ parent.translate( 'reports' ) }}</span>
            </router-link>
        </li>
        <li :class="{ 'nav-item': true, active:  routeName === 'logs', premium: !parent.is_premium }">
            <div v-if="!parent.is_premium" class="premium-badge">
                <i class="mdi mdi-star"></i>
                <span>Premium</span>
            </div>
            <router-link class="nav-link" :to="{ name: 'logs' }">
                <i class="menu-icon mdi mdi-checkbox-multiple-blank-outline"></i>
                <span class="menu-title">{{ parent.translate( 'custom_report' ) }}</span>
            </router-link>
        </li>
        <li :class="{ 'nav-item': true, active: routeName === 'online-users', premium: !parent.is_premium }">
            <div v-if="!parent.is_premium" class="premium-badge">
                <i class="mdi mdi-star"></i>
                <span>Premium</span>
            </div>
            <router-link class="nav-link" :to="{ name: 'online-users' }">
                <i class="menu-icon mdi mdi-face"></i>
                <span class="menu-title">{{ parent.translate( 'online_users' ) }}</span>
            </router-link>
        </li>
        <li :class="{ 'nav-item': true, active: routeName === 'settings' }">
            <router-link class="nav-link" :to="{ name: 'settings' }">
                <i class="menu-icon mdi mdi-settings-outline"></i>
                <span class="menu-title">{{ parent.translate( 'settings' ) }}</span>
            </router-link>
        </li>
        <li :class="{ 'nav-item': true, active: routeName === 'events' }">
            <router-link class="nav-link" :to="{ name: 'events' }">
                <i class="menu-icon mdi mdi-check-circle-outline"></i>
                <span class="menu-title">{{ parent.translate( 'events_control' ) }}</span>
            </router-link>
        </li>
    </ul>
</nav>
    `,
    data() {
        return {
            parent: this.$parent,
            routeName: null,
            pluginUrl: reports_vars.plugin_url,
        }
    },
    watch: {
        $route( to, from ) {
            this.routeName = to.name;
        }
    }
}