import Vue from 'vue';
import Vuex from 'vuex';

Vue.use(Vuex)

export default new Vuex.Store({
  // state
  state: {
    active_nav_index: 0,
    settings: {},
    fields: {},

    submenu: {
      general: [
        'General',
        'Packages',
        'Other',
      ],
    },
    
  },
  
  // mutations
  mutations: {
    swichNav: ( state, index ) => {
      state.active_nav_index = index;
    },

    updateSettings: ( state, value ) => {
      state.settings = value;
    },

    updateFields: ( state, value ) => {
      state.fields = value;
    },

    updateFieldValue: ( state, payload ) => {
      state.fields[ payload.field_key ].value = payload.value;
    },

    updateGeneralSectionData: ( state, payload ) => {
      state.settings.general.submenu.general.sections[ payload.section_key ].fields[ payload.field_key ].value = payload.value;
    },

    updateValidationStatus: ( state, payload ) => {
      
    }
  },

  getters: {

  }

});