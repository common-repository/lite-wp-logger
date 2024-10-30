export default {
    template: `
<div class="main-panel">
    <div class="content-wrapper">
        <div class="mb-3"><h2>{{parent.translate('online_users')}}</h2></div>
        <div class="row">
            <div class="col-lg-12 d-flex flex-column">
                <div class="row flex-grow">
                    <div class="col-12 grid-margin stretch-card">
                        <div class="card card-rounded online-users">
                            <div class="card-body">
                                <div class="d-sm-flex justify-content-between align-items-start">
                                    <div class="col">
                                        <h4 class="card-title card-title-dash">{{ parent.translate( 'logged_in_users' ) }}</h4>
                                        <p class="card-subtitle card-subtitle-dash">{{ parent.translate( 'latest_logged_in_users' ) }}</p>
                                    </div>
                                    <div class="col-auto">
                                        <a href="#" :class="{ 'left btn btn-primary btn-sm text-white me-0': true, 
                                        disabled: (sessions === 'loading' || tAllLoading || !parent.is_premium) }" @click.prevent="terminateAll">
                                        {{ parent.translate( 'terminate_all' ) }}
                                        </a>
                                    </div>
                                </div>
                                <div class="table-responsive mt-1">
                                    <table class="table select-table">
                                        <thead>
                                        <tr>
                                            <th style="width: 5%">{{ parent.translate( 'id' ) }}</th>
                                            <th style="width: 29%">{{ parent.translate( 'user' ) }}</th>
                                            <th style="width: 22%">{{ parent.translate( 'create' ) }}</th>
                                            <th style="width: 22%">{{ parent.translate( 'expire' ) }}</th>
                                            <th style="width: 22%">{{ parent.translate( 'actions' ) }}</th>
                                        </tr>
                                        </thead>
                                        <tbody v-if="sessions === 'loading'">
                                            <tr v-for="lindex in 3" :key="'loadin-user-'+lindex">
                                                <td>
                                                    <div class="grad-loading" style="height: 20px;"></div>
                                                </td>
                                                <td>
                                                    <div class="grad-loading" style="height: 20px;"></div>
                                                </td>
                                                <td>
                                                    <div class="grad-loading" style="height: 20px;"></div>
                                                </td>
                                                <td>
                                                    <div class="grad-loading" style="height: 20px;"></div>
                                                </td>
                                                <td>
                                                    <div class="grad-loading" style="height: 32px; width: 88px; border-radius: 30px;"></div>
                                                </td>
                                            </tr>
                                        </tbody>
                                        <tbody v-else-if="sessions">
                                            <tr v-for="(session, sindex) in sessions" :key="'session-'+sindex">
                                                <td>
                                                    <h6>{{ session.user.ID }}</h6>
                                                </td>
                                                <td>
                                                    <h6>{{ session.user.user_login }} ({{ session.user.display_name }})</h6>
                                                </td>
                                                <td>
                                                    <h6 v-if="session.createTimeNow">{{ session.createTimeNow }}</h6>
                                                    <h6 v-else>---</h6>
                                                    <span v-if="session.createTimeFull">{{ session.createTimeFull }}</span>
                                                    <span v-else>---</span>
                                                </td>
                                                <td>
                                                    <h6 v-if="session.expiryTimeNow">{{ session.expiryTimeNow }}</h6>
                                                    <h6 v-else>---</h6>
                                                    <span v-if="session.expiryTimeFull">{{ session.expiryTimeFull }}</span>
                                                    <span v-else>---</span>
                                                </td>
                                                <td>
                                                    <a v-if="session.user && session.user.ID" href="#" :class="{ 'left btn btn-primary btn-sm text-white me-0': true, 
                                                    disabled: Number(currentUserID) === Number(session.user.ID) || session.loading }" 
                                                    @click.prevent="terminate(sindex)">{{ parent.translate( 'terminate' ) }}</a>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
    `,
    data() {
        return {
            parent: this.$parent.$parent,
            sessions: 'loading',
            currentUserID: null,
            tAllLoading: false,
            dateTimeout: null,
        }
    },
    mounted() {
        this.parent.setPage('online-users')
        this.getCurrentUser()
        this.getSessions()
    },
    beforeUnmount() {
        if ( this.dateTimeout )
            clearTimeout( this.dateTimeout );
    },
    methods: {
        getCurrentUser() {
            this.parent.sendAjax('getCurrentUser')
                .then((response) => {
                    this.currentUserID = response.ID;
                })
        },
        getSessions() {
            this.parent.sendAjax('getSessions')
                .then((response) => {
                    for ( let sessionI in response ) {
                        if( response[ sessionI ].creation_time )
                            response[ sessionI ].createTimeFull =
                                moment( response[ sessionI ].creation_time ).format( "MMM D YYYY, HH:mm:ss" );
                        if( response[ sessionI ].expiry_time )
                            response[ sessionI ].expiryTimeFull =
                                moment( response[ sessionI ].expiry_time ).format( "MMM D YYYY, HH:mm:ss" );
                        response[ sessionI ].createTimeNow = '...';
                        response[ sessionI ].expiryTimeNow = '...';
                    }
                    this.sessions = response;
                    this.dateUpdate();
                })
        },
        dateUpdate() {
            clearTimeout( this.dateTimeout );
            for ( let sessionI in this.sessions ){
                if ( this.sessions[ sessionI ].creation_time )
                    this.sessions[ sessionI ].createTimeNow =
                        moment( this.sessions[ sessionI ].creation_time ).fromNow();
                if ( this.sessions[ sessionI ].expiry_time )
                    this.sessions[ sessionI ].expiryTimeNow =
                        moment( this.sessions[ sessionI ].expiry_time ).fromNow();
            }
            this.dateTimeout = setTimeout( () => {
                this.dateUpdate();
            }, 30000 );
        },
        terminate(sindex) {
            let uid = this.sessions[sindex].user.ID
            if(Number(this.currentUserID) !== Number(uid) && !this.sessions[sindex].loading){
                this.sessions[sindex].loading = true
                this.parent.sendAjax('removeSession', { uid: uid })
                    .then((response) => {
                        this.sessions = 'loading'
                        this.getSessions()
                    })
            }
        },
        terminateAll() {
            if(!this.tAllLoading){
                this.tAllLoading = true
                this.parent.sendAjax('removeAllSessions')
                    .then((response) => {
                        this.sessions = 'loading'
                        this.getSessions()
                        this.tAllLoading = false
                    })
            }
        },
    },
}