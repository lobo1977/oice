<template>
  <div>
    <tab v-if="id > 0">
      <tab-item selected @on-item-click="tab = 0">基本信息</tab-item>
      <tab-item @on-item-click="tab = 1" :disabled="id===0">英文信息</tab-item>
      <tab-item @on-item-click="tab = 2" :disabled="id===0">图片</tab-item>
    </tab>

    <div v-transfer-dom v-if="images.length">
      <previewer :list="images" ref="prevBuildingEdit" :options="previewOptions">
        <template slot="button-before">
          <span class="previewer-icon" @click.prevent.stop="confirmRemoveImage" title="删除">
            <x-icon type="trash-a" size="24"></x-icon>
          </span>
          <span class="previewer-icon" @click.prevent.stop="setDefault" title="设为封面图">
            <x-icon type="image" size="24"></x-icon>
          </span>
        </template>
      </previewer>
    </div>

    <div v-show="tab === 0">
      <group gutter="0" label-width="4em" label-margin-right="1em" label-align="right">
        <x-input ref="inpBuildingName" title="项目名称" v-model="info.building_name" :required="true" :max="30"
          @on-click-error-icon="nameError" :should-toast-error="false" @on-change="validateForm"></x-input>
        
        <popup-picker title="项目类型" :data="typePickerList" @on-change="typeChange" v-model="typeValue" value-text-align="left"></popup-picker>
        <cell title="等级" @click.native="showLevelPicker = true" :is-link="true" :value="info.level" value-align="left"></cell>
        <popup-picker title="所在地" :data="districtPickerList" :columns=2 @on-change="districtChange" :value="districtValue" value-text-align="left"></popup-picker>

        <x-input title="详细地址" v-model="info.address" :max="100" :show-clear="false">
          <span slot="right" style="position:relative;top:3px;left:3px" @click="showMap = true">
            <x-icon type="location" size="24" class="input_icon"></x-icon>
          </span>
        </x-input>
        <cell title="竣工日期" :value="info.completion_date" value-align="left" :is-link="true" @click.native="selectCompletionDate"></cell>
        <cell title="租售" @click.native="showRentSellPicker = true" :is-link="true" :value="info.rent_sell" value-align="left"></cell>
        <x-input title="价格" v-model="info.price" :max="30"></x-input>
        
        <x-input v-if="false" title="建筑面积" type="tel" :max="8" v-model="info.acreage" :show-clear="false">
          <span slot="right">平方米</span>
        </x-input>
        <x-input title="楼层" v-model="info.floor" :max="10"></x-input>
        <x-input title="层面积" type="tel" :max="6" v-model="info.floor_area" :show-clear="false">
          <span slot="right">平方米</span>
        </x-input>
        <cell title="层高" value-align="left">
          <inline-x-number style="float:left;margin:0 5px 0 0;" width="80px" :min="2" :max="8" :step="0.1" v-model="info.floor_height"></inline-x-number>
          <div style="float:left;display:inline-block;line-height:28px;">米</div>
        </cell>
        <x-input title="楼板承重" type="tel" :max="6" v-model="info.bearing" :show-clear="false">
          <span slot="right">千克/平方米</span>
        </x-input>
        <x-input title="开发商" v-model="info.developer" :max="50"></x-input>
        <x-input title="物业管理" v-model="info.manager" :max="50"></x-input>
        <x-input title="物业费" v-model="info.fee" :max="30"></x-input>
        <x-input title="电费" v-model="info.electricity_fee" :max="50"></x-input>
        <x-input title="停车位" v-model="info.car_seat" :max="50"></x-input>
      </group>

      <group title="项目说明">
        <x-textarea :rows="3" v-model="info.rem" :max="500"></x-textarea>
      </group>

      <group title="楼宇设备">
        <x-textarea :rows="3" v-model="info.equipment" :max="500"></x-textarea>
      </group>

      <group title="交通状况">
        <x-textarea :rows="3" v-model="info.traffic" :max="500"></x-textarea>
      </group>

      <group title="配套设施">
        <x-textarea :rows="3" v-model="info.facility" :max="500"></x-textarea>
      </group>

      <group title="周边环境">
        <x-textarea :rows="3" v-model="info.environment" :max="500"></x-textarea>
      </group>

      <group v-if="info.user_id == 0 || info.user_id == user.id" gutter="10px" label-width="4em" label-margin-right="1em" label-align="right">
        <cell title="所属企业" @click.native="selectCompany" :is-link="companyPickerData.length != 1" 
          :value="companyText" value-align="left"></cell>
        <x-switch title="是否公开" inline-desc="是否全网可见" v-model="info.share"></x-switch>
      </group>

      <actionsheet v-model="showRentSellPicker" :menus="rentSellPickerList" theme="android" @on-click-menu="rentSellSelect"></actionsheet>
      <actionsheet v-model="showLevelPicker" :menus="levelPickerList" theme="android" @on-click-menu="levelSelect"></actionsheet>
      <actionsheet v-model="showCompanyPicker" :menus="companyPickerData" theme="android" @on-click-menu="companySelect"></actionsheet>

      <div class="bottom-bar">
        <x-button type="primary" class="bottom-btn" @click.native="save" :disabled="!formValidate">
          <x-icon type="android-archive" class="btn-icon"></x-icon> 保存
        </x-button>
      </div>
    </div>

    <div v-if="tab === 1">
      <group gutter="0" label-width="4em" label-margin-right="1em" label-align="right">
        <x-input ref="inpEngName" title="项目名称" v-model="engInfo.name" :required="true" :max="50"
          @on-click-error-icon="nameError" :should-toast-error="false" @on-change="validateEngForm"></x-input>
      </group>

      <group title="地理位置">
        <x-textarea :rows="3" v-model="engInfo.location" :max="500"></x-textarea>
      </group>

      <group title="物业规模">
        <x-textarea :rows="3" v-model="engInfo.situation" :max="500"></x-textarea>
      </group>

      <group title="开发商">
        <x-textarea :rows="3" v-model="engInfo.developer" :max="100"></x-textarea>
      </group>

      <group title="物业管理">
        <x-textarea :rows="3" v-model="engInfo.manager" :max="100"></x-textarea>
      </group>

      <group title="通讯设施">
        <x-textarea :rows="3" v-model="engInfo.network" :max="500"></x-textarea>
      </group>

      <group title="电梯">
        <x-textarea :rows="3" v-model="engInfo.elevator" :max="500"></x-textarea>
      </group>

      <group title="中央空调">
        <x-textarea :rows="3" v-model="engInfo.hvac" :max="500"></x-textarea>
      </group>

      <group title="配套设施">
        <x-textarea :rows="3" v-model="engInfo.amenities" :max="500"></x-textarea>
      </group>

      <group title="入驻公司">
        <x-textarea :rows="3" v-model="engInfo.tenants" :max="500"></x-textarea>
      </group>

      <div class="bottom-bar">
        <x-button type="primary" class="bottom-btn" @click.native="saveEngInfo" :disabled="!engFormValidate">
          <x-icon type="android-archive" class="btn-icon"></x-icon> 保存
        </x-button>
      </div>
    </div>

    <div v-if="tab === 2">
      <flexbox :gutter="0" wrap="wrap">
        <flexbox-item :span="1/4" v-for="(item, index) in images" :key="index">
          <img :src="item.msrc" @click="preview(index)" class="building-img">
        </flexbox-item>
      </flexbox>
      <div class="bottom-bar">
        <form ref="frmUploadBuildingImage">
          <x-button type="warn" class="bottom-btn">上传</x-button>
          <input type="hidden" name="id" :value="id">
          <input type="file" class="upload" name="images[]" multiple="multiple"
            accept="image/*" @change="upload"/>
        </form>
      </div>
    </div>

    <popup v-model="showMap" position="bottom"
      height="100%" style="overflow-y:hidden;">
      <baidumap :get-point="false" :is-edit="true"
        :is-shown="showMap"
        :title="info.building_name"
        :district="info.area"
        :address="info.address"
        :longitude="info.longitude" 
        :latitude="info.latitude"
        @on-close="closeMap" @on-confirm="setLocation"></baidumap>
    </popup>

  </div>
</template>

<script>
import { Previewer, TransferDom } from 'vux'
import Baidumap from '@/components/BaiduMap.vue'
import typeData from '../../data/building_type.json'
import districtData from '../../data/beijing_area.json'
import rentSellData from '../../data/rent_sell.json'
import levelData from '../../data/building_level.json'

export default {
  directives: {
    TransferDom
  },
  components: {
    Previewer,
    Baidumap
  },
  data () {
    return {
      user: {
        id: 0
      },
      tab: 0,
      id: 0,
      formValidate: false,
      engFormValidate: false,
      info: {
        __token__: '',
        building_name: '',    // 名称
        type: '',             // 类别
        level: '',            // 等级
        area: '',             // 城区
        district: '',         // 商圈
        address: '',          // 地址
        longitude: 0,          // 经度
        latitude: 0,           // 纬度
        completion_date: '', // 竣工日期
        rent_sell: '',        // 租售
        price: '',            // 价格
        acreage: null,        // 建筑面积
        // usage_area: null,  // 使用面积
        floor: '',            // 楼层
        floor_area: null,     // 标准层面积
        floor_height: null,   // 层高
        bearing: null,        // 楼板承重
        developer: '',        // 开发商
        manager: '',          // 物业管理
        fee: '',              // 物业费
        electricity_fee: '',  // 电费
        car_seat: '',         // 停车位
        rem: '',              // 项目说明
        facility: '',         // 配套设施
        equipment: '',        // 楼宇设备
        traffic: '',          // 交通状况
        environment: '',      // 周边环境
        company_id: '',       // 所属企业
        user_id: 0,
        share: false          // 是否公开
      },
      engInfo: {
        __token__: '',
        name: '',
        location: '',
        situation: '',
        developer: '',
        manager: '',
        network: '',
        elevator: '',
        hvac: '',
        amenities: '',
        tenants: ''
      },
      images: [],
      previewOptions: {
        isClickableElement: function (el) {
          return /previewer-icon/.test(el.className)
        }
      },
      typeValue: [''],
      districtValue: ['', ''],
      showTypePicker: false,
      showDistrictPicker: false,
      showRentSellPicker: false,
      showLevelPicker: false,
      typePickerList: [typeData],
      districtPickerList: districtData,
      rentSellPickerList: rentSellData,
      levelPickerList: levelData,
      showCompanyPicker: false,
      companyPickerData: [],
      companyText: '',
      showMap: false
    }
  },
  beforeRouteEnter (to, from, next) {
    next(vm => {
      vm.user = vm.$store.state.oice.user || vm.user

      if (to.params.id) {
        vm.id = parseInt(to.params.id)
        if (isNaN(vm.id)) {
          vm.id = 0
        }
      }

      vm.$get('/api/building/edit?id=' + vm.id, (res) => {
        if (res.success) {
          if (res.data.companyList) {
            for (let i in res.data.companyList) {
              vm.companyPickerData.push({
                label: res.data.companyList[i].title,
                value: res.data.companyList[i].id
              })
              if (res.data.companyList[i].active === 1) {
                vm.info.company_id = res.data.companyList[i].id
                vm.companyText = res.data.companyList[i].title
              }
            }
          }
          if (vm.id) {
            for (let item in vm.info) {
              if (res.data[item] !== undefined && res.data[item] !== null) {
                if (item === 'company_id') {
                  if (res.data.company_id > 0) {
                    vm.info.company_id = res.data.company_id
                    for (let i in vm.companyPickerData) {
                      if (vm.info.company_id === vm.companyPickerData[i].value) {
                        vm.companyText = vm.companyPickerData[i].label
                        break
                      }
                    }
                  }
                } else {
                  vm.info[item] = res.data[item]
                }
              }
            }
            if (vm.info.type) {
              vm.typeValue = [vm.info.type]
            }
            if (vm.info.area || vm.info.district) {
              vm.districtValue = [vm.info.area, vm.info.district]
            }
            if (vm.info.completion_date) {
              vm.info.completion_date = vm.$dateFormat(new Date(Date.parse(vm.info.completion_date.replace(/-/g, '/'))), 'YYYY-MM-DD')
            }
            if (!vm.info.acreage || vm.info.acreage === '0') {
              vm.info.acreage = null
            }
            // if (!vm.info.usage_area || vm.info.usage_area === '0') {
            //   vm.info.usage_area = null
            // }
            if (!vm.info.floor_area || vm.info.floor_area === '0') {
              vm.info.floor_area = null
            }
            if (!vm.info.floor_height || vm.info.floor_height === '0') {
              vm.info.floor_height = null
            }
            if (!vm.info.bearing || vm.info.bearing === '0') {
              vm.info.bearing = null
            }
            if (res.data.images) {
              vm.images = res.data.images
            }
            if (vm.info.company_id === 0 && vm.companyPickerData.length) {
              vm.info.company_id = vm.companyPickerData[0].value
              vm.companyText = vm.companyPickerData[0].label
            }
            vm.info.share = vm.info.share === 1
            if (res.data.engInfo) {
              for (let item in vm.engInfo) {
                if (res.data.engInfo[item] !== undefined && res.data.engInfo[item] !== null) {
                  vm.engInfo[item] = res.data.engInfo[item]
                }
              }
            }
            vm.engInfo.__token__ = res.data.__token__
            vm.$emit('on-view-loaded', vm.info.building_name)
          } else {
            vm.info.__token__ = res.data.__token__
            vm.$emit('on-view-loaded', '添加项目')
          }
        } else {
          vm.$vux.toast.show({
            text: res.message,
            width: '16em'
          })
        }
      })
    })
  },
  methods: {
    nameError () {
      this.$vux.toast.show({
        text: '请输入项目名称'
      })
    },
    validateForm () {
      this.formValidate = this.$refs.inpBuildingName.valid
    },
    validateEngForm () {
      this.engFormValidate = this.$refs.inpEngName.valid
    },
    typeChange (val) {
      this.info.type = val[0]
    },
    levelSelect (key, item) {
      this.info.level = item.value
    },
    districtChange (val) {
      this.info.area = val[0] === 'all' ? '' : val[0]
      this.info.district = val[1]
      this.districtValue = [this.info.area, this.info.district]
    },
    rentSellSelect (key, item) {
      this.info.rent_sell = item.value
    },
    selectCompletionDate () {
      let vm = this
      vm.$vux.datetime.show({
        value: vm.info.completion_date,
        cancelText: '取消',
        confirmText: '确定',
        onConfirm (val) {
          vm.info.completion_date = val
        }
      })
    },
    companySelect (key, item) {
      this.info.company_id = item.value
      this.companyText = item.label
    },
    selectCompany () {
      if (this.companyPickerData.length > 1) {
        this.showCompanyPicker = true
      } else if (this.companyPickerData.length === 0) {
        let vm = this
        vm.$vux.confirm.show({
          title: '选择企业',
          content: '您还没有加入企业，是否立即创建或加入企业？',
          onConfirm () {
            vm.$router.push({name: 'Company'})
          }
        })
      }
    },
    closeMap () {
      this.showMap = false
    },
    setLocation (location) {
      this.info.longitude = location.longitude
      this.info.latitude = location.latitude
      this.info.address = location.address
      this.info.area = location.district
      this.districtValue = [location.district, '']
    },
    save () {
      this.validateForm()
      if (!this.formValidate) {
        return
      }
      this.$vux.loading.show()
      this.info.share = (this.info.share ? 1 : 0)
      this.$post('/api/building/edit?id=' + this.id, this.info, (res) => {
        this.$vux.loading.hide()
        if (res.success) {
          if (this.id === 0) {
            this.id = res.data
            this.$router.replace({name: 'BuildingView', params: {id: this.id}})
          } else {
            this.$router.back()
          }
        } else {
          this.info.__token__ = res.data
          this.engInfo.__token__ = res.data
          this.$vux.toast.show({
            text: res.message,
            width: '16em'
          })
        }
      })
    },
    saveEngInfo () {
      this.validateEngForm()
      if (!this.engFormValidate) {
        return
      }
      this.$vux.loading.show()
      this.$post('/api/building/saveEngInfo?id=' + this.id, this.engInfo, (res) => {
        this.$vux.loading.hide()
        if (res.data) {
          this.info.__token__ = res.data
          this.engInfo.__token__ = res.data
        }
        if (res.success) {
          this.$vux.toast.show({
            text: '保存成功。',
            width: '10em'
          })
        } else {
          this.$vux.toast.show({
            text: res.message,
            width: '16em'
          })
        }
      })
    },
    preview (index) {
      this.$refs.prevBuildingEdit.show(index)
    },
    upload () {
      let form = this.$refs.frmUploadBuildingImage
      this.$vux.loading.show()
      this.$postFile('/api/building/uploadImage', form, (res) => {
        this.$vux.loading.hide()
        if (res.success) {
          this.images = res.data
        } else {
          this.$vux.toast.show({
            text: res.message,
            width: '16em'
          })
        }
      })
    },
    setDefault () {
      let index = this.$refs.prevBuildingEdit.getCurrentIndex()
      if (index < 0) return
      this.$vux.loading.show()
      this.$post('/api/building/setDefaultImage', {
        image_id: this.images[index].id
      }, (res) => {
        this.$vux.loading.hide()
        if (res.success) {
          for (let i in this.images) {
            if (i === index) {
              this.images[i].defalut = 1
            } else {
              this.images[i].defalut = 0
            }
          }
          this.$vux.toast.show({
            type: 'success',
            text: '封面设置成功'
          })
        } else {
          this.$vux.toast.show({
            text: res.message,
            width: '16em'
          })
        }
      })
    },
    confirmRemoveImage () {
      let index = this.$refs.prevBuildingEdit.getCurrentIndex()
      if (index < 0) return
      let img = this.images[index]
      if (img.default === 1) {
        this.$vux.toast.show({
          text: '封面图不能删除',
          width: '16em'
        })
        return
      }
      let vm = this
      this.$vux.confirm.show({
        title: '删除图片',
        content: '确定要删除这张图片吗？',
        onConfirm () {
          vm.removeImage(index, img.id)
        }
      })
    },
    removeImage (index, imgId) {
      this.$vux.loading.show()
      this.$post('/api/building/removeImage', {
        image_id: imgId
      }, (res) => {
        this.$vux.loading.hide()
        if (res.success) {
          this.images.splice(index, 1)
        } else {
          this.$vux.toast.show({
            text: res.message,
            width: '13em'
          })
        }
      })
    }
  }
}
</script>

<style lang="less">
.building-img { width:100%; height: auto; }

.input_icon {
  fill: #aaa;
}

.bottom-bar {
  .upload {
    position: absolute;
    top: 0;
    width: 100%;
    height: 3em;
    opacity: 0;
  }
}

.previewer-icon {
  display:block;
  float:right;
  opacity: 0.75;
  cursor: pointer;
  width:44px;
  height:44px;
  svg {
    margin:10px;fill:#fff;
  }
}
</style>