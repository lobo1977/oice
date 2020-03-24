<template>
  <div>
    <tab v-show="false">
      <tab-item @on-item-click="tab = 0" selected>基本信息</tab-item>
      <tab-item @on-item-click="tab = 1" :disabled="id === 0"></tab-item>
    </tab>

    <div v-if="tab === 0">
      <group gutter="0" label-width="4em" label-margin-right="1em" label-align="right">
        <x-input ref="inpCustomerName" title="客户名称" v-model="info.customer_name" :required="true" :max="30"
          @on-click-error-icon="nameError" :should-toast-error="false" @on-change="validateForm"></x-input>
        <x-input ref="inpCustomerTel" title="直线电话" placeholder="请输入直线电话" v-model="info.tel" :max="30"></x-input>
        <x-input v-if="id === 0" ref="inpCustomerLinkman" title="联系人" v-model="info.linkman" :max="30"></x-input>
        <popup-picker title="所在地" :data="districtPickerData" @on-change="districtChange"
          :columns="2" :fixed-columns="1"
          v-model="districtValue" value-text-align="left"></popup-picker>
        <x-input title="详细地址" v-model="info.address" :max="100"></x-input>
      </group>

      <group gutter="10px" label-width="4em" label-margin-right="1em" label-align="right">
        <popup-picker title="需求项目" :data="typePickerData" @on-change="typeChange" v-model="typeValue" value-text-align="left"></popup-picker>
        <cell title="租购" @click.native="showleaseBuyPicker = true" is-link :value="info.lease_buy" value-align="left"></cell>
        <cell title="意向区域" @click.native="showDistrictSelect = true" is-link :value="info.district" value-align="left"></cell>

        <x-input title="最小面积" type="tel" :max="5" v-model="info.min_acreage" :show-clear="false">
          <span slot="right">平方米</span>
        </x-input>
        <x-input title="最大面积" type="tel" :max="5" v-model="info.max_acreage" :show-clear="false">
          <span slot="right">平方米</span>
        </x-input>

        <x-input title="预算" v-model="info.budget" :max="30"></x-input>
        <cell title="入驻日期" :value="info.settle_date" value-align="left" :is-link="true" @click.native="selectSettleDate"></cell>

        <x-input title="在驻面积" type="tel" :max="5" v-model="info.current_area" :show-clear="false">
          <span slot="right">平方米</span>
        </x-input>
        <cell title="到期日" :value="info.end_date" value-align="left" :is-link="true" @click.native="selectEndDate"></cell>
        <cell title="到期提醒" value-align="left">
            <div style="float:left;display:inline-block;line-height:28px;">提前</div>
            <inline-x-number style="float:left;margin:0 5px;" width="40px" :min="0" :max="10" v-model="info.remind"></inline-x-number>
            <div style="float:left;display:inline-block;line-height:28px;">个月提醒</div>
        </cell>
      </group>

      <group gutter="10px">
        <x-textarea placeholder="备注" :rows="3" v-model="info.rem" :max="500"></x-textarea>
      </group>

      <group gutter="10px" label-width="4em" label-margin-right="1em" label-align="right">
        <cell title="状态" @click.native="showStatusPicker = true" :is-link="true" :value="statusText" value-align="left"></cell>
        <cell title="所属企业" @click.native="selectCompany" :is-link="companyPickerData.length != 1" 
          :value="companyText" value-align="left"></cell>
        <x-switch title="是否共享" inline-desc="共享给企业内部成员" v-model="info.boolShare"></x-switch>
      </group>

      <popup v-model="showDistrictSelect">
        <popup-header
          left-text="取消"
          right-text="完成"
          @on-click-left="showDistrictSelect = false"
          @on-click-right="confirmSelectDistrict"></popup-header>
        <checker v-model="selectedDistrict"
          type="checkbox"
          default-item-class="checker-item"
          selected-item-class="checker-item-selected">
          <checker-item v-for="i in districtSelectData" :key="i" :value="i">{{i}}</checker-item>
        </checker>
      </popup>

      <actionsheet v-model="showleaseBuyPicker" :menus="leaseBuyPickerData" theme="android" @on-click-menu="leaseBuySelect"></actionsheet>
      <actionsheet v-model="showStatusPicker" :menus="statusPickerData" theme="android" @on-click-menu="statusSelect"></actionsheet>
      <actionsheet v-model="showCompanyPicker" :menus="companyPickerData" theme="android" @on-click-menu="companySelect"></actionsheet>

      <div class="bottom-bar">
        <x-button type="primary" class="bottom-btn" @click.native="save" :disabled="!formValidate">
          <x-icon type="android-archive" class="btn-icon"></x-icon> 保存
        </x-button>
      </div>
    </div>

    <div v-if="tab === 1">
    </div>
    
  </div>
</template>

<script>
import { PopupHeader, Checker, CheckerItem } from 'vux'
import typeData from '../../data/building_type.json'
import districtData from '../../data/beijing_area.json'
import leaseBuyData from '../../data/lease_buy.json'
import statusData from '../../data/customer_status.json'

export default {
  components: {
    PopupHeader,
    Checker,
    CheckerItem
  },
  data () {
    return {
      tab: 0,
      id: 0,
      formValidate: false,
      info: {
        __token__: '',
        customer_name: '',    // 名称
        tel: '',              // 直线电话
        area: '',             // 城区
        address: '',          // 地址
        demand: '',           // 需求项目
        lease_buy: '',        // 租赁/购买
        district: '',         // 意向商圈
        min_acreage: null,    // 最小面积(平方米)
        max_acreage: null,    // 最大面积(平方米)
        budget: '',           // 预算
        settle_date: '',      // 入驻日期
        current_area: null,   // 在驻面积
        end_date: '',         // 到期日
        remind: 8,            // 到期提醒（提前月份数）
        rem: '',              // 项目说明
        status: 6,            // 状态
        company_id: 0,        // 所属企业
        share: 0,
        boolShare: false,     // 共享状态
        linkman: '',          // 联系人
        clash: 0              // 撞单客户
      },
      showDistrictPicker: false,
      districtPickerData: [],
      districtValue: ['', ''],
      showTypePicker: false,
      typePickerData: [typeData],
      typeValue: [''],
      showleaseBuyPicker: false,
      leaseBuyPickerData: leaseBuyData,
      showDistrictSelect: false,
      districtSelectData: [],
      selectedDistrict: [],
      showStatusPicker: false,
      statusPickerData: statusData,
      statusText: '',
      showCompanyPicker: false,
      companyPickerData: [],
      companyText: '',
      flag: '',
      bid: 0,
      uid: ''
    }
  },
  beforeRouteEnter (to, from, next) {
    next(vm => {
      for (let i in districtData) {
        if (districtData[i].parent && districtData[i].value) {
          if (vm.districtSelectData.indexOf(districtData[i].value) < 0) {
            vm.districtSelectData.push(districtData[i].value)
          }
        } else if (districtData[i].value !== 'all') {
          vm.districtPickerData.push(districtData[i])
        }
      }

      if (to.params.id) {
        vm.id = parseInt(to.params.id)
        if (isNaN(vm.id)) {
          vm.id = 0
        }
      }

      if (to.query) {
        if (to.query.flag) {
          vm.flag = to.query.flag
        }
        if (to.query.bid) {
          vm.bid = to.query.bid
        }
        if (to.query.uid) {
          vm.uid = to.query.uid
        }
      }

      vm.$get('/api/customer/edit?id=' + vm.id, (res) => {
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
            if (vm.info.area) {
              vm.districtValue = [vm.info.area, '']
            }
            if (vm.info.demand) {
              vm.typeValue = [vm.info.demand]
            }
            if (vm.info.district) {
              vm.selectedDistrict = vm.info.district.split(',')
            }
            if (vm.info.settle_date) {
              vm.info.settle_date = vm.$dateFormat(new Date(Date.parse(vm.info.settle_date.replace(/-/g, '/'))), 'YYYY-MM-DD')
            }
            if (vm.info.end_date) {
              vm.info.end_date = vm.$dateFormat(new Date(Date.parse(vm.info.end_date.replace(/-/g, '/'))), 'YYYY-MM-DD')
            }
            if (!vm.info.min_acreage || vm.info.min_acreage === '0') {
              vm.info.min_acreage = null
            }
            if (!vm.info.max_acreage || vm.info.max_acreage === '0') {
              vm.info.max_acreage = null
            }
            if (!vm.info.current_area || vm.info.current_area === '0') {
              vm.info.current_area = null
            }
            if (vm.info.status >= 0 && vm.info.status < vm.statusPickerData.length) {
              vm.statusText = vm.statusPickerData[vm.info.status].label
            }
            vm.info.boolShare = vm.info.share === 1
            vm.$emit('on-view-loaded', vm.info.customer_name)
          } else {
            vm.info.__token__ = res.data.__token__
            if (vm.info.status >= 0 && vm.info.status < vm.statusPickerData.length) {
              vm.statusText = vm.statusPickerData[vm.info.status].label
            }
            vm.$emit('on-view-loaded', '添加客户')
          }
          if (vm.info.company_id === 0 && vm.companyPickerData.length) {
            vm.info.company_id = vm.companyPickerData[0].value
            vm.companyText = vm.companyPickerData[0].label
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
        text: '请输入客户名称'
      })
    },
    telError () {
      this.$vux.toast.show({
        text: this.info.tel.length ? '直线电话无效' : '请输入直线电话'
      })
    },
    linkmanError () {
      this.$vux.toast.show({
        text: '请输入联系人'
      })
    },
    validateForm () {
      if (this.id === 0) {
        this.formValidate = this.$refs.inpCustomerName.valid // &&
          // this.$refs.inpCustomerTel.valid && this.$refs.inpCustomerLinkman.valid
      } else {
        this.formValidate = this.$refs.inpCustomerName.valid
      }
    },
    districtChange (val) {
      this.info.area = (val[0] === 'all') ? '' : val[0]
    },
    typeChange (val) {
      this.info.demand = val[0]
    },
    leaseBuySelect (key, item) {
      this.info.lease_buy = item
    },
    confirmSelectDistrict () {
      this.info.district = this.selectedDistrict.join()
      this.showDistrictSelect = false
    },
    statusSelect (key, item) {
      this.info.status = item.value
      this.statusText = item.label
    },
    selectSettleDate () {
      let vm = this
      vm.$vux.datetime.show({
        value: vm.info.settle_date,
        cancelText: '取消',
        confirmText: '确定',
        onConfirm (val) {
          vm.info.settle_date = val
        }
      })
    },
    selectEndDate () {
      let vm = this
      vm.$vux.datetime.show({
        value: vm.info.end_date,
        cancelText: '取消',
        confirmText: '确定',
        onConfirm (val) {
          vm.info.end_date = val
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
    save () {
      let vm = this
      vm.validateForm()
      if (!vm.formValidate) {
        return
      }
      vm.$vux.loading.show()
      vm.info.share = (vm.info.boolShare ? 1 : 0)
      let url = '/api/customer/edit?id=' + vm.id
      if (vm.bid || vm.uid) {
        url = url + '&flag=' + vm.flag + '&bid=' + vm.bid + '&uid=' + vm.uid
      }
      vm.$post(url, vm.info, (res) => {
        vm.$vux.loading.hide()
        if (res.success) {
          if (res.message) {
            vm.$vux.alert.show({
              title: '保存成功',
              content: res.message,
              onHide () {
                if (vm.id === 0) {
                  vm.id = res.data
                  vm.$router.replace({name: 'CustomerView', params: {id: vm.id}})
                } else {
                  vm.$router.back()
                }
              }
            })
          } else if (vm.flag === 'confirm') {
            vm.$router.replace({name: 'ConfirmEdit', params: {id: 0, bid: vm.bid, cid: res.data}})
          } else if (vm.id === 0) {
            vm.$router.replace({name: 'CustomerView', params: {id: res.data}})
          } else {
            vm.$router.back()
          }
        } else if (res.data) {
          if (res.data.token) {
            vm.info.__token__ = res.data.token
          }
          if (res.data.confirm && res.data.clash) {
            vm.$vux.confirm.show({
              title: '撞单提醒',
              content: res.message,
              onConfirm () {
                vm.info.clash = res.data.clash
                vm.save()
              }
            })
          } else {
            vm.$vux.alert.show({
              title: res.data.clash ? '撞单提醒' : '发生错误',
              content: res.message,
              onHide () {
                if (res.data.clash) {
                  vm.$router.push({name: 'CustomerView', params: {id: res.data.clash}})
                }
              }
            })
          }
        } else {
          vm.$vux.alert.show({
            title: '发生错误',
            content: res.message
          })
        }
      })
    }
  },
  computed: {
  }
}
</script>

<style lang="less">
.vux-checker-box {
  padding-left:10px;
  padding-bottom:10px;
  background-color:#fff;
}

.checker-item {
  background-color: #ddd;
  color: #222;
  font-size: 14px;
  padding: 5px 10px;
  margin-top: 10px;
  margin-right: 10px;
  line-height: 18px;
  border-radius: 4px;
}

.checker-item-selected {
  background-color: rgb(6, 165, 27);
  color: #fff;
}
</style>