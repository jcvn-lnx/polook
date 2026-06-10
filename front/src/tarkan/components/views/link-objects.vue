<template>
  <el-dialog :lock-scroll="true" :top="'50px'" v-model="show" title="Teste">
      <template v-slot:title>
          <div style="border-bottom: #e0e0e0 1px solid; padding: 20px">
              <div class="modal-title" style="display: flex; width: calc(100% - 50px)">
                  <el-input v-model="query" :placeholder="KT(search)" style="--el-input-border-radius: 5px; margin-right: 5px"></el-input>
              </div>
          </div>
      </template>
      
      <template v-slot:footer> </template>
      
      <div class="itm" style="display: flex; background: #eeeeee">
          <div @click="toggleSorting('check')" style="width: 50px; padding: 10px; font-size: 12px">
              <span v-if="orderFlag === 'check-asc'">
                  <i class="fas fa-sort-alpha-down"></i>
              </span>
              <span v-else-if="orderFlag === 'check-desc'">
                  <i class="fas fa-sort-alpha-up"></i>
              </span>
              <span v-else>
                  <i style="color: silver" class="fas fa-sort-alpha-down"></i>
              </span>
          </div>
          <div @click="toggleSorting('id')" style="width: 30px; text-align: center; padding: 10px; font-size: 12px">
              Id

              <span v-if="orderFlag === 'id-asc'">
                  <i class="fas fa-sort-alpha-down"></i>
              </span>
              <span v-else-if="orderFlag === 'id-desc'">
                  <i class="fas fa-sort-alpha-up"></i>
              </span>
              <span v-else>
                  <i style="color: silver" class="fas fa-sort-alpha-down"></i>
              </span>
          </div>
          <div @click="toggleSorting('name')" style="flex: 1; padding: 10px; font-size: 12px; text-align: center">
              Nome

              <span v-if="orderFlag === 'name-asc'">
                  <i class="fas fa-sort-alpha-down"></i>
              </span>
              <span v-else-if="orderFlag === 'name-desc'">
                  <i class="fas fa-sort-alpha-up"></i>
              </span>
              <span v-else>
                  <i style="color: silver" class="fas fa-sort-alpha-down"></i>
              </span>
          </div>
      </div>
      <div style="height: calc(100vh - 300px); overflow: hidden; overflow-y: scroll">
        <paginate :items="filteredObjects" :per-page="10" v-model="currentPage">
              <template v-slot="{item, index}">
                <div class="itm" :class="{ tr1: index % 2, tr2: !(k % 2) }" style="display: flex">
                  <div style="width: 50px; padding: 10px; font-size: 14px">
                      <el-switch :model-value="isLinked(item.id)" @change="changeLink(u, $event)" :size="'large'"></el-switch>
                  </div>
                  <div style="width: 30px; text-align: center; padding: 10px; font-size: 14px">
                      {{ item.id }}
                  </div>
                  <template v-if="objectType === 'notifications' && !item.attributes['description']">
                      <div style="flex: 1; padding: 10px; font-size: 14px; text-align: center">{{ KT('notification.types.' + item.type) }}</div>
                      <div style="flex: 1; padding: 10px; font-size: 14px; text-align: center">
                          <template v-if="item.notificators.split(',').length">
                              <span class="tblItem" v-for="(a, b) in item.notificators.split(',')" :key="b">{{ KT('notification.channels.' + a, a) }}</span>
                          </template>
                      </div>
                      <div style="flex: 1; padding: 10px; font-size: 14px; text-align: center">
                          <template v-if="item.attributes['alarms']">
                              <span class="tblItem" v-for="(a, b) in item.attributes['alarms'].split(',')" :key="b">{{ KT('alarms.' + a, a) }}</span>
                          </template>
                      </div>
                  </template>
                  <div v-else-if="objectType === 'devices'" style="flex: 1; padding: 10px; font-size: 14px; text-align: center"
                      >{{ item.name || item.description || item.attributes['description'] }} | {{ item.attributes['placa'] }}</div
                  >
                  <div v-else style="flex: 1; padding: 10px; font-size: 14px; text-align: center">{{
                      item.name || item.description || item.attributes['description']
                  }}</div>
              </div>
              </template>
          </paginate>  
      </div>
  </el-dialog>
</template>

<script setup>
import { ref, defineExpose, provide, computed } from 'vue';
import Paginate from '../../../components/base/Paginate.vue';

import 'element-plus/es/components/input/style/css';
import 'element-plus/es/components/button/style/css';
import 'element-plus/es/components/switch/style/css';
import 'element-plus/es/components/dialog/style/css';

import { ElDialog, ElSwitch, ElInput } from 'element-plus';

const currentPage = ref(1);
const query = ref('');
const show = ref(false);
const search = ref('search');

const objectType = ref(null);

const selection = ref({});
const keyType = ref(null);
const availableObjects = ref([]);
const selectedObjects = ref([]);

const orderFlag = ref('name-asc');

const toggleSorting = (s) => {
  const p = orderFlag.value.split('-');

  if (p[0] === s) {
      orderFlag.value = s + '-' + (p[1] === 'asc' ? 'desc' : 'asc');
  } else {
      orderFlag.value = s + '-asc';
  }
};

import KT from '../../func/kt';

const isLinked = (id) => {
  return selectedObjects.value.find((f) => f.id === id) ? true : false;
};

const changeLink = (obj, state) => {
  let tmp = JSON.parse(JSON.stringify(selection.value));
  tmp[keyType.value] = obj.id;
  if (state) {
      selectedObjects.value.push(obj);

      window.$traccar.linkObjects(tmp);
  } else {
      selectedObjects.value.splice(
          selectedObjects.value.findIndex((f) => f.id === obj.id),
          1,
      );

      window.$traccar.unlinkObjects(tmp);
  }
};

const sortFunc = (a, b, p) => {
  if (p[0] === 'check') {
      const aa = isLinked(a.id);
      const bb = isLinked(b.id);

      if (p[1] === 'asc') {
          return aa === true && bb === false ? 1 : -1;
      } else {
          return aa === true && bb === false ? -1 : 1;
      }
  } else if (p[0] === 'name') {
      const aa = a.name || a.description || a.attributes['description'] || '';
      const bb = b.name || b.description || b.attributes['description'] || '';

      if (p[1] === 'asc') {
          return aa.localeCompare(bb);
      } else {
          const t = aa.localeCompare(bb);
          return t === 1 ? -1 : t === -1 ? 1 : 0;
      }
  } else if (!a[p[0]] || !b[p[0]]) {
      return p[1] === 'desc' ? 1 : -1;
  } else if (a[p[0]] > b[p[0]]) {
      return p[1] === 'asc' ? 1 : -1;
  } else if (a[p[0]] < b[p[0]]) {
      return p[1] === 'desc' ? 1 : -1;
  } else {
      return 0;
  }
};

const filteredObjects = computed(() => {
  const p = orderFlag.value.split('-');

  if (query.value.length < 3) {
      return [...availableObjects.value].sort((a, b) => {
          return sortFunc(a, b, p);
      });
  } else {
      return availableObjects.value
          .filter((f) => {
              for (let k of Object.keys(f)) {
                  if (String(f[k]).toLowerCase().match(query.value.toLowerCase())) {
                      return true;
                  }
              }

              for (let k of Object.keys(f.attributes)) {
                  if (String(f.attributes[k]).toLowerCase().match(query.value.toLowerCase())) {
                      return true;
                  }
              }

              return false;
          })
          .sort((a, b) => {
              return sortFunc(a, b, p);
          });
  }
});

const showObjects = (params) => {
  selection.value = {};

  objectType.value = params.type;
  selection.value[Object.keys(params)[0]] = params[Object.keys(params)[0]];

  if (params.type === 'geofences') {
      keyType.value = 'geofenceId';
      search.value = 'geofence.search';

      window.$traccar.getGeofences({ all: true }).then(({ data }) => {
          availableObjects.value = data;
      });

      window.$traccar.getGeofences(selection.value).then(({ data }) => {
          selectedObjects.value = data;
      });
  } else if (params.type === 'devices') {
      keyType.value = 'deviceId';
      search.value = 'device.search';

      window.$traccar.getDevices({ all: true }).then(({ data }) => {
          let tmp = [];

          data.forEach((d) => {
              if (!(d.uniqueId.split('-').length == 3 && d.uniqueId.split('-')[0] === 'deleted')) {
                  tmp.push(d);
              }
          });

          availableObjects.value = tmp;
      });

      window.$traccar.getDevices(selection.value).then(({ data }) => {
          selectedObjects.value = data;
      });
  } else if (params.type === 'commands') {
      keyType.value = 'commandId';
      search.value = 'command.search';

      window.$traccar.getSavedCommands({ all: true }).then(({ data }) => {
          availableObjects.value = data;
      });

      window.$traccar.getSavedCommands(selection.value).then(({ data }) => {
          selectedObjects.value = data;
      });
  } else if (params.type === 'maintence') {
      keyType.value = 'maintenanceId';
      search.value = 'maintenance.search';

      window.$traccar.getMaintenance({ all: true }).then(({ data }) => {
          availableObjects.value = data;
      });

      window.$traccar.getMaintenance(selection.value).then(({ data }) => {
          selectedObjects.value = data;
      });
  } else if (params.type === 'attributes') {
      keyType.value = 'attributeId';
      search.value = 'attribute.search';

      window.$traccar.getComputedAttributes({ all: true }).then(({ data }) => {
          availableObjects.value = data;
      });

      window.$traccar.getComputedAttributes(selection.value).then(({ data }) => {
          selectedObjects.value = data;
      });
  } else if (params.type === 'calendars') {
      keyType.value = 'calendarId';
      search.value = 'calendar.search';

      window.$traccar.getCalendars({ all: true }).then(({ data }) => {
          availableObjects.value = data;
      });

      window.$traccar.getCalendars(selection.value).then(({ data }) => {
          selectedObjects.value = data;
      });
  } else if (params.type === 'notifications') {
      keyType.value = 'notificationId';
      search.value = 'notification.search';

      window.$traccar.getNotifications({ all: true }).then(({ data }) => {
          availableObjects.value = data;
      });

      window.$traccar.getNotifications(selection.value).then(({ data }) => {
          selectedObjects.value = data;
      });
  } else if (params.type === 'users') {
      keyType.value = 'managedUserId';
      search.value = 'user.search';

      window.$traccar.getUsers({ all: true }).then(({ data }) => {
          availableObjects.value = data;
      });

      window.$traccar.getUsers(selection.value).then(({ data }) => {
          selectedObjects.value = data;
      });
  } else if (params.type === 'groups') {
      keyType.value = 'groupId';
      search.value = 'group.search';

      window.$traccar.getGroups({ all: true }).then(({ data }) => {
          availableObjects.value = data;
      });

      window.$traccar.getGroups(selection.value).then(({ data }) => {
          selectedObjects.value = data;
      });
  } else if (params.type === 'drivers') {
      keyType.value = 'driverId';
      search.value = 'driver.search';

      window.$traccar.getDrivers({ all: true }).then(({ data }) => {
          availableObjects.value = data;
      });

      window.$traccar.getDrivers(selection.value).then(({ data }) => {
          selectedObjects.value = data;
      });
  }

  show.value = true;
};

provide('showObjects', showObjects);

defineExpose({
  showObjects,
});
</script>

<style>
.itm {
  border-bottom: silver 1px dotted;
}

.itm div {
  border-right: silver 1px dotted;
}

.tr1 {
  background: #f3f3f3;
}

.tr2 {
  background: #f8f8f8;
}

.selected {
  background: rgba(5, 167, 227, 0.05) !important;
}

.itm div:last-child {
  border-right: none;
}

.el-select.el-select--large {
  width: 100%;
}

.el-dialog__footer {
  margin-top: 0px;
}

.el-tabs__nav-wrap {
  padding-left: 20px;
  padding-right: 20px;
}

.el-tabs__content {
  padding-left: 20px;
  padding-right: 20px;
}

.danger {
  --el-button-text-color: var(--el-color-danger) !important;
  --el-button-bg-color: #fef0f0 !important;
  --el-button-border-color: #fbc4c4 !important;
  --el-button-hover-text-color: var(--el-color-white) !important;
  --el-button-hover-bg-color: var(--el-color-danger) !important;
  --el-button-hover-border-color: var(--el-color-danger) !important;
  --el-button-active-text-color: var(--el-color-white) !important;
  --el-button-active-border-color: var(--el-color-danger) !important;
}

.tblItem:after {
  content: ', ';
}

.tblItem:last-child:after {
  content: '';
}
</style>