export default {
    template: `
<div class="main-panel">
    <div class="content-wrapper">
        <div class="mb-3"><h2>{{parent.translate('events_control')}}</h2></div>
        <div class="row">
            <div class="col-lg-12 d-flex flex-column">
                <div class="row flex-grow">
                    <div class="col-12 grid-margin stretch-card">
                        <div class="card card-rounded">
                            <div class="card-body">
                                <div class="d-sm-flex justify-content-between align-items-start">
                                    <div class="col">
                                        <h4 class="card-title card-title-dash">{{parent.translate('events_control')}}</h4>
                                        <p class="card-subtitle card-subtitle-dash">{{parent.translate('events_control_settings')}}</p>
                                    </div>
                                </div>
                                
                                <ul class="nav nav-pills mb-3" id="settings-tab" v-if="settingsFields === 'loading'">
                                  <li class="nav-item" v-for="lindex in 4" :key="'tab-'+lindex">
                                    <div class="grad-loading grad-loading-inline" style="
                                    width: 75px; height: 30px; border-radius: 30px;"></div>
                                  </li>
                                </ul>
                                <ul class="nav nav-pills mb-3" id="settings-tab" v-else-if="settingsFields">
                                  <li 
                                  v-for="(settingField, settingName) in settingsFields" 
                                  :key="'setting-'+settingName"
                                  class="nav-item">
                                    <button 
                                    :class="{'btn btn-sm btn-primary': true, active: settingName === activeTab}" 
                                    :id="'settings-'+settingName+'-tab'" 
                                    data-bs-toggle="pill" 
                                    :data-bs-target="'#settings-'+settingName" 
                                    type="button"
                                    :aria-controls="'settings-'+settingName" 
                                    :aria-selected="settingName === activeTab">{{parent.translate(settingName)}}</button>
                                  </li>
                                </ul>
                                
                                <div class="tab-content" id="settings-tabContent" v-if="settingsFields === 'loading' || settings === 'loading'">
                                  <div 
                                  class="tab-pane fade show active" 
                                  id="settings-loading" 
                                  aria-labelledby="settings-loading-tab">
                                  <div class="form-group" v-for="lsindex in 4" :key="'setting-tab-'+lsindex">
                                        <div class="row" style="margin-bottom: 1.75rem;">
                                            <div class="col-md-3">
                                                <div class="grad-loading" style="height: 22px;"></div>
                                            </div>
                                            <div class="col-md-5">
                                                <div class="grad-loading" style="height: 22px; max-width: 80px;"></div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="grad-loading" style="height: 22px; max-width: 80px;"></div>
                                            </div>
                                        </div>
                                    </div>
                                  </div>
                                </div>
                                <div class="tab-content" id="settings-tabContent" v-else-if="settings">
                                  <div 
                                  v-for="(settingField, settingName) in settingsFields" 
                                  :key="'setting-content-'+settingName"
                                  :class="{'tab-pane fade': true, 'show active': settingName === activeTab}" 
                                  :id="'settings-'+settingName" 
                                  :aria-labelledby="'settings-'+settingName+'-tab'">
                                    <div 
                                    v-for="(settingCell, index) in settingField" 
                                    :key="'setting-'+settingName+'-'+index"
                                    class="form-group">
                                        <div class="row">
                                            <div class="col-md-3">
                                                <label class="item-label" :for="settingCell.id">
                                                    <div v-if="settingCell.is_premium && !parent.is_premium" class="premium-badge">
                                                        <i class="mdi mdi-star"></i>
                                                        <span>{{parent.translate('premium')}}</span>
                                                    </div>
                                                    <span>{{settingCell.name}}</span>
                                                </label>
                                            </div>
                                            <div class="col-md-5">
                                                <template v-if="settingCell.type === 'checkbox'">
                                                    <label :for="settingCell.id" class="checkbox-cont">
                                                      <input :id="settingCell.id" 
                                                      :disabled="settingCell.is_premium && !parent.is_premium"
                                                      type="checkbox" @change="makeDirty"
                                                      v-model="settings[settingCell.id]">
                                                      <span class="checkmark"></span>
                                                    </label>
                                                </template>
                                                <template v-else>
                                                    <input :id="settingCell.id" type="text"
                                                    :disabled="settingCell.is_premium && !parent.is_premium"
                                                    @change="makeDirty"v-model="settings[settingCell.id]">
                                                </template>
                                                <small v-if="settingCell.desc">{{ settingCell.desc }}</small>
                                            </div>
                                            <div class="col-md-4">
                                                <template v-if="emailSettings[settingCell.id] !== null">
                                                    <label :for="settingCell.id+'email'" class="checkbox-cont">
                                                      <input :id="settingCell.id+'email'" 
                                                      :disabled="!parent.is_premium"
                                                      type="checkbox" @change="makeDirty"
                                                      v-model="emailSettings[settingCell.id]">
                                                      <span class="checkmark"></span>
                                                    </label>
                                                    <small class="fw-bold" v-if="settingCell.desc">
                                                        <div v-if="!parent.is_premium" class="premium-badge">
                                                            <i class="mdi mdi-star"></i>
                                                            <span>{{parent.translate('premium')}}</span>
                                                        </div>
                                                        {{ parent.translate('email_notify') }}
                                                    </small>
                                                </template>
                                            </div>
                                        </div>
                                    </div>
                                  </div>
                                </div>
                                 <div class="bottom-row" v-if="settings === 'loading'">
                                    <a href="#" class="left btn text-white me-0 btn-primary disabled">{{ parent.translate('save') }}</a>
                                </div>
                                <div class="bottom-row" v-else>
                                    <a href="#" :class="{'left btn text-white me-0':true,
                                    'btn-primary': saveLoading === 0, 'btn-info': saveLoading === 2, 
                                    'btn-primary disabled': saveLoading === 1, 
                                    }" @click.prevent="saveEventsSettings">{{ (saveLoading === 2)? parent.translate('saved'):parent.translate('save') }}</a>
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
            saveLoading: 0,
            settings: 'loading',
            emailSettings: 'loading',
            settingsFields: 'loading',
            activeTab: 'login',
            formDirty: false,
        }
    },
    mounted() {
        window.addEventListener( 'beforeunload', this.beforeWindowUnload )
        this.parent.setPage('events')
        this.getEventsSettingsFields()
    },
    beforeUnmount() {
        window.removeEventListener( 'beforeunload', this.beforeWindowUnload )
    },
    beforeRouteLeave(to, from, next) {
        if ( this.confirmStayInDirtyForm() ) next(false)
        else next()
    },
    methods: {
        getEventsSettingsFields() {
            this.parent.sendAjax('getEventsSettingsFields')
                .then((response) => {
                    this.settingsFields = response;
                    this.getEventsSettings()
                })
        },
        getEventsSettings() {
            this.parent.sendAjax('getEventsSettings')
                .then((response) => {
                    if(Array.isArray(response.settings)){
                        let {...objCon} = response.settings
                        response.settings = objCon
                    }
                    if(Array.isArray(response.emails)){
                        let {...objConE} = response.emails
                        response.emails = objConE
                    }
                    for(let secSets in this.settingsFields)
                        for(let setItem in this.settingsFields[secSets]) {
                            if (
                                !response.settings.hasOwnProperty(this.settingsFields[secSets][setItem].id) ||
                                (this.settingsFields[secSets][setItem].is_premium && !this.parent.is_premium)
                            ){
                                response.settings[this.settingsFields[secSets][setItem].id] = this.settingsFields[secSets][setItem].std
                            }
                            if(this.settingsFields[secSets][setItem].type === 'checkbox'){
                                response.settings[this.settingsFields[secSets][setItem].id] = Number(response.settings[this.settingsFields[secSets][setItem].id])
                                response.settings[this.settingsFields[secSets][setItem].id] = !!(response.settings[this.settingsFields[secSets][setItem].id]);
                            }
                        }
                    this.settings = response.settings;

                    for(let secSets in this.settingsFields)
                        for(let setItem in this.settingsFields[secSets]) {
                            if (
                                !response.emails.hasOwnProperty(this.settingsFields[secSets][setItem].id) ||
                                !this.parent.is_premium
                            ){
                                response.emails[this.settingsFields[secSets][setItem].id] = this.settingsFields[secSets][setItem].email
                            }
                            if(response.emails[this.settingsFields[secSets][setItem].id] !== null){
                                response.emails[this.settingsFields[secSets][setItem].id] = Number(response.emails[this.settingsFields[secSets][setItem].id])
                                response.emails[this.settingsFields[secSets][setItem].id] = !!(response.emails[this.settingsFields[secSets][setItem].id]);
                            }
                        }
                    this.emailSettings = response.emails;
                })
        },
        saveEventsSettings() {
            if(this.saveLoading !== 1){
                this.saveLoading = 1
                let settingsData =  JSON.parse(JSON.stringify(this.settings))
                for(let secSets in this.settingsFields)
                    for(let setItem in this.settingsFields[secSets])
                        if(this.settingsFields[secSets][setItem].type === 'checkbox')
                            settingsData[this.settingsFields[secSets][setItem].id] =
                                (settingsData[this.settingsFields[secSets][setItem].id])? '1':'0';

                let emailSettingsData
                if (this.parent.is_premium) {
                    emailSettingsData = JSON.parse(JSON.stringify(this.emailSettings))
                    for (let secSets in this.settingsFields)
                        for (let setItem in this.settingsFields[secSets])
                            if (emailSettingsData[this.settingsFields[secSets][setItem].id] !== null)
                                emailSettingsData[this.settingsFields[secSets][setItem].id] =
                                    (emailSettingsData[this.settingsFields[secSets][setItem].id]) ? '1' : '0';
                            else delete emailSettingsData[this.settingsFields[secSets][setItem].id]
                }else emailSettingsData = null

                this.parent.sendAjax('saveEventsSettings',{
                    events_settings: settingsData,
                    events_email_settings: emailSettingsData
                }).then((response) => {
                    this.saveLoading = 2
                    this.getEventsSettings()
                    this.formDirty = false;
                    setTimeout(()=>{
                        this.saveLoading = 0
                    },2000)
                }).catch((err) => {
                    alert('Something went wrong. Try again')
                    console.log(err)
                })
            }
        },
        makeDirty() {
            this.formDirty = true;
        },
        confirmLeave() {
            return window.confirm( this.parent.translate( 'unsaved_changes' ) )
        },
        confirmStayInDirtyForm() {
            return this.formDirty && !this.confirmLeave()
        },
        beforeWindowUnload(event) {
            if (this.confirmStayInDirtyForm()) {
                // Cancel the event
                event.preventDefault()
                // Chrome requires returnValue to be set
                event.returnValue = ''
            }
        },
    },
}