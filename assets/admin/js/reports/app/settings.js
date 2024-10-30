export default {
    template: `
<div class="main-panel">
    <div class="content-wrapper">
        <div class="mb-3"><h2>{{parent.translate('settings')}}</h2></div>
        <div class="row">
            <div class="col-lg-12 d-flex flex-column">
                <div class="row flex-grow">
                    <div class="col-12 grid-margin stretch-card">
                        <div class="card card-rounded">
                            <div class="card-body">
                                <div class="d-sm-flex justify-content-between align-items-start">
                                    <div class="col">
                                        <h4 class="card-title card-title-dash">{{parent.translate('settings')}}</h4>
                                        <p class="card-subtitle card-subtitle-dash">{{parent.translate('plugin_settings')}}</p>
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
                                    :aria-selected="settingName === activeTab">{{ parent.translate( settingName ) }}</button>
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
                                            <div class="col-md-9">
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
                                                        <span>{{ parent.translate( 'premium' ) }}</span>
                                                    </div>
                                                    <span>{{ settingCell.name }}</span>
                                                </label>
                                            </div>
                                            <div class="col-md-9">
                                                <template v-if="settingCell.type === 'checkbox'">
                                                    <label :for="settingCell.id" class="checkbox-cont">
                                                      <input :id="settingCell.id" 
                                                       :disabled="settingCell.is_premium && !parent.is_premium"
                                                       type="checkbox" @change="makeDirty" v-model="settings[settingCell.id]">
                                                      <span class="checkmark"></span>
                                                    </label>
                                                </template>
                                                <template v-else-if="settingCell.type === 'multi_select'">
                                                    <select class="select2" :id="settingCell.id" :name="settingCell.id+'[]'"
                                                        :disabled="settingCell.is_premium && !parent.is_premium"
                                                        v-model="settings[settingCell.id]" @change="makeDirty" multiple>
                                                        <template v-if="settingCell.options && 
                                                            Array.isArray(settingCell.options)">
                                                            <option v-for="option in settingCell.options"
                                                            :value="option.value">{{ option.name }}</option>
                                                        </template>
                                                        <template v-if="settingCell.optionsItems && 
                                                            Array.isArray(settingCell.optionsItems)">
                                                            <option v-for="option in settingCell.optionsItems"
                                                            :value="option.value">{{ option.name }}</option>
                                                        </template>
                                                    </select>
                                                </template>
                                                <template v-else-if="settingCell.type === 'select'">
                                                    <select class="select2" :id="settingCell.id" @change="makeDirty" :name="settingCell.id+'[]'"
                                                        :disabled="settingCell.is_premium && !parent.is_premium"
                                                        v-model="settings[settingCell.id]">
                                                        <template v-if="settingCell.options && 
                                                            Array.isArray(settingCell.options) && settingCell.options[0]">
                                                            <option v-for="option in settingCell.options"
                                                            :value="option.value">{{ option.name }}</option>
                                                        </template>
                                                        <template v-if="settingCell.optionsItems && 
                                                            Array.isArray(settingCell.optionsItems)">
                                                            <option v-for="option in settingCell.optionsItems"
                                                            :value="option.value">{{ option.name }}</option>
                                                        </template>
                                                    </select>
                                                </template>
                                                <template v-else-if="settingCell.type === 'number'">
                                                    <div class="form-number">
                                                        <i :class="{ 'mdi mdi-plus form-number-up': true , 'disabled': settingCell.is_premium && !parent.is_premium }" 
                                                        @click="moveNumber(settingCell.id, 'up', (settingCell.is_premium && !parent.is_premium), $event)"></i>
                                                        
                                                        <i :class="{ 'mdi mdi-minus form-number-down': true , 'disabled': settingCell.is_premium && !parent.is_premium }" 
                                                        @click="moveNumber(settingCell.id, 'down', (settingCell.is_premium && !parent.is_premium), $event)"></i>
                                                        
                                                        <input :id="settingCell.id" type="number" @change="makeDirty"
                                                            :disabled="settingCell.is_premium && !parent.is_premium"
                                                            :max="(settingCell.max)? settingCell.max : ''"
                                                            :min="(settingCell.max)? settingCell.min : 0"
                                                            v-model="settings[settingCell.id]">
                                                    </div>
                                                </template>
                                                <template v-else-if="settingCell.type === 'adder'">
                                                    <div class="form-adder">
                                                        <input :id="settingCell.id" type="text"
                                                            :disabled="settingCell.is_premium && !parent.is_premium">
                                                        <button @click="adder( settingName, index, $event )" 
                                                        :disabled="settingCell.is_premium && !parent.is_premium"
                                                        :class="{ 'btn text-white me-0 btn-primary': true, disabled: settingCell.loading }">
                                                            {{ parent.translate( 'add' ) }}
                                                        </button>
                                                        <div class="adder-data">
                                                            <div v-for="(settingItem, sindex) in settings[settingCell.id]" 
                                                            :key="'setting-item'+sindex" class="adder-item">
                                                                <i @click="removeAdder( sindex, settingCell.id, ( settingCell.is_premium && !parent.is_premium ) )" 
                                                                class="mdi mdi-close"></i>
                                                                <span>{{ settingItem }}</span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </template>
                                                <template v-else>
                                                    <input :id="settingCell.id" type="text" @change="makeDirty" 
                                                        :disabled="settingCell.is_premium && !parent.is_premium"
                                                        v-model="settings[settingCell.id]">
                                                </template>
                                                <small v-if="settingCell.desc">{{ settingCell.desc }}</small>
                                            </div>
                                        </div>
                                    </div>
                                  </div>
                                </div>
                                <div class="bottom-row" v-if="settings === 'loading'">
                                    <a href="#" class="left btn text-white me-0 btn-primary disabled">{{ parent.translate( 'save' ) }}</a>
                                </div>
                                <div class="bottom-row" v-else>
                                    <a href="#" :class="{'left btn text-white me-0':true,
                                    'btn-primary': saveLoading === 0, 'btn-info': saveLoading === 2, 
                                    'btn-primary disabled': saveLoading === 1, 
                                    }" @click.prevent="saveSettings">
                                    {{ ( saveLoading === 2 )? parent.translate( 'saved' ) : parent.translate( 'save' ) }}
                                    </a>
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
            settingsFields: 'loading',
            activeTab: 'general',
            initedSelect2: false,
            formDirty: false,
        }
    },
    mounted() {
        window.addEventListener( 'beforeunload', this.beforeWindowUnload )
        this.parent.setPage('settings')
        this.getSettingsFields()
    },
    beforeUnmount() {
        window.removeEventListener( 'beforeunload', this.beforeWindowUnload )
    },
    beforeRouteLeave(to, from, next) {
        if ( this.confirmStayInDirtyForm() ) next(false)
        else next()
    },
    methods: {
        getSettingsFields() {
            this.parent.sendAjax('getSettingsFields')
                .then((response) => {
                    this.settingsFields = response;
                    this.getSettings()
                })
        },
        getSettings() {
            this.parent.sendAjax('getSettings')
                .then((response) => {
                    for (let secSets in this.settingsFields) {
                        for (let setItem in this.settingsFields[secSets]) {
                            if (
                                !response.hasOwnProperty(this.settingsFields[secSets][setItem].id) ||
                                (this.settingsFields[secSets][setItem].is_premium && !this.parent.is_premium)
                            ) {
                                response[this.settingsFields[secSets][setItem].id] =
                                    JSON.parse(JSON.stringify(this.settingsFields[secSets][setItem].std))
                            }
                            if (this.settingsFields[secSets][setItem].type === 'checkbox') {
                                response[this.settingsFields[secSets][setItem].id] = Number(response[this.settingsFields[secSets][setItem].id])
                                response[this.settingsFields[secSets][setItem].id] = !!(response[this.settingsFields[secSets][setItem].id]);
                            } else if (
                                !this.initedSelect2 &&
                                (this.settingsFields[secSets][setItem].type === 'multi_select' ||
                                this.settingsFields[secSets][setItem].type === 'select')
                            ) {
                                setTimeout(() => {
                                    this.select2(secSets, setItem)
                                }, 150)
                            }
                        }
                    }
                    this.settings = response;
                })
        },
        saveSettings() {
            if(this.saveLoading !== 1){
                this.saveLoading = 1
                let settingsData = JSON.parse(JSON.stringify(this.settings))
                for(let secSets in this.settingsFields)
                    for(let setItem in this.settingsFields[secSets]){
                        if(this.settingsFields[secSets][setItem].type === 'checkbox')
                            settingsData[this.settingsFields[secSets][setItem].id] =
                                (settingsData[this.settingsFields[secSets][setItem].id])? '1':'0';
                        if(Array.isArray(settingsData[this.settingsFields[secSets][setItem].id]) &&
                            !settingsData[this.settingsFields[secSets][setItem].id][0])
                            settingsData[this.settingsFields[secSets][setItem].id] = 'empty_array'
                    }

                this.parent.sendAjax('saveSettings',{
                    settings: settingsData
                }).then((response) => {
                    this.saveLoading = 2
                    this.getSettings()
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
        initSelect2() {
            jQuery('.select2').on('select2:select', (event) => {
                event.target.dispatchEvent(new Event('change'));
            }).on('select2:unselect', (event) => {
                event.target.dispatchEvent(new Event('change'));
            });
            this.initedSelect2 = true
        },
        select2(secSets, setItem) {
            let itemId = this.settingsFields[secSets][setItem].id
            let options = this.settingsFields[secSets][setItem].options
            let select2Options = {};

            if(options === 'users'){
                if(this.settings[itemId] && this.settings[itemId][0]) {
                    this.parent.sendAjax('getUsers', {
                        per_page: -1,
                        ids: JSON.parse(JSON.stringify(this.settings[itemId]))
                    }).then((response) => {
                        this.settingsFields[secSets][setItem].optionsItems = []
                        for (let uindex in response) {
                            this.settingsFields[secSets][setItem].optionsItems.push({
                                value: response[uindex].ID,
                                name: response[uindex].user_login +
                                    ((Number(response[uindex].ID) !== 0)? ' ('+ response[uindex].display_name+')':''),
                            })
                        }
                    })
                }
                select2Options = {
                    ajax: {
                        type: 'POST',
                        dataType : "json",
                        contentType: "application/x-www-form-urlencoded; charset=UTF-8",
                        url: reports_vars.url,
                        processResults: (data, params) => {
                            return {
                                results: jQuery.map(data, function(item) {
                                    return {
                                        id: item.ID,
                                        text: item.user_login + ((Number(item.ID) !== 0)? ' ('+item.display_name+')':''),
                                    }
                                }),
                                pagination: {
                                    more: data.length
                                }
                            }
                        },
                        data: (params) => {
                            return {
                                search: params.term,
                                nonce: reports_vars.nonce,
                                action: reports_vars.plugin_name + '_getUsers',
                                page: params.page
                            }
                        }
                    },
                }
            }else if(options === 'roles') {
                this.parent.sendAjax('getUserRoles')
                    .then((response) => {
                        this.settingsFields[secSets][setItem].optionsItems = []
                        for (let rindex in response){
                            this.settingsFields[secSets][setItem].optionsItems.push({
                                value: rindex,
                                name: response[rindex]
                            })
                        }
                    })
            }else if(options === 'post_types'){
                this.parent.sendAjax('getPostTypes')
                    .then((response) => {
                        this.settingsFields[secSets][setItem].optionsItems = []
                        for (let pindex in response){
                            this.settingsFields[secSets][setItem].optionsItems.push({
                                value: pindex,
                                name: response[pindex]
                            })
                        }
                    })
            }
            jQuery('#' + itemId).select2(select2Options);
            this.initSelect2()
        },
        adder( secSets, setItem, event ) {
            if ( ! this.settingsFields[ secSets ][ setItem ].loading ){
                let options     = this.settingsFields[ secSets ][ setItem ].options
                let itemId      = this.settingsFields[ secSets ][ setItem ].id
                let input       = event.target.parentElement.children[0].value
                if ( input ) {
                    if ( this.settings[ itemId ].findIndex( item => item === input ) === -1 ) {
                        this.settingsFields[ secSets ][ setItem ].loading = true
                        if ( options === 'ips' ) {
                            if ( this.validateIp( input ) ){
                                this.parent.sendAjax( 'checkValidIp', {
                                    ip: input
                                } ).then( ( response ) => {
                                    if ( response === true ) {
                                        this.settings[ itemId ].push( input )
                                        this.makeDirty();
                                        event.target.parentElement.children[0].value = ''
                                    } else
                                        alert( this.parent.translate( 'the_ip_is_not_valid' ) )
                                    this.settingsFields[ secSets ][ setItem ].loading = false
                                } )
                            } else {
                                this.settingsFields[ secSets ][ setItem ].loading = false
                                alert( this.parent.translate( 'the_ip_is_not_valid' ) )
                            }
                        } else if ( options === 'emails' ) {
                            if ( this.validateEmail( input ) ){
                                this.parent.sendAjax( 'checkValidEmail', {
                                    email: input
                                } ).then( ( response ) => {
                                    if ( response === true ) {
                                        this.settings[ itemId ].push( input )
                                        this.makeDirty();
                                        event.target.parentElement.children[0].value = ''
                                    } else
                                        alert( this.parent.translate( 'the_email_is_not_valid' ) )
                                    this.settingsFields[ secSets ][ setItem ].loading = false
                                } )
                            } else {
                                this.settingsFields[ secSets ][ setItem ].loading = false
                                alert( this.parent.translate( 'the_email_is_not_valid' ) )
                            }
                        } else if ( options === 'options' ) {
                            this.parent.sendAjax( 'checkValidOption', {
                                option: input
                            } ).then( ( response ) => {
                                if ( response === true ) {
                                    this.settings[ itemId ].push( input )
                                    this.makeDirty();
                                    event.target.parentElement.children[0].value = ''
                                } else
                                    alert( this.parent.translate( 'the_option_is_not_valid' ) )
                                this.settingsFields[ secSets ][ setItem ].loading = false
                            } )
                        } else {
                            this.settings[ itemId ].push( input )
                            this.makeDirty();
                            event.target.parentElement.children[0].value = ''
                            this.settingsFields[ secSets ][ setItem ].loading = false
                        }
                    } else {
                        alert( this.parent.translate( 'this_item_exists' ) )
                        this.settingsFields[ secSets ][ setItem ].loading = false
                    }
                }

            }
        },
        moveNumber(itemIndex, action, disabled, event) {
            if(!disabled){
                let max = Number(event.target.parentElement.children[2].attributes.max.value)
                let min = Number(event.target.parentElement.children[2].attributes.min.value)
                if(action === 'up'){
                    if (max != 0) {
                        if ( Number(this.settings[itemIndex]) < max ) {
                            this.settings[itemIndex] = Number(this.settings[itemIndex]) + 1
                            this.makeDirty()
                        }
                    } else {
                        this.settings[itemIndex] = Number(this.settings[itemIndex]) + 1
                        this.makeDirty()
                    }
                } else {
                    if( Number(this.settings[itemIndex]) > min ) {
                        this.settings[itemIndex] = Number(this.settings[itemIndex]) - 1
                        this.makeDirty()
                    }
                }
            }
        },
        removeAdder(settingItemIndex, settingId, disabled){
            if(!disabled)
                this.settings[settingId].splice(settingItemIndex,1);
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
        validateEmail(email) {
            return String(email)
                .toLowerCase()
                .match(
                    /^(([^<>()[\]\\.,;:\s@"]+(\.[^<>()[\]\\.,;:\s@"]+)*)|(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/
                );
        },
        validateIp(ip) {
            return (/^(25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.(25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.(25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.(25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)$/.test(
                ip
            ));
        },
    },
}