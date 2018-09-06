<template>
  <div>
      <group gutter="0" label-width="5em" label-margin-right="1em" label-align="right">
        <cell value-align="left" :value="building.building" 
          :link="{name: 'BuildingView', params: { id: info.building_id }}">
          <span slot="title" :class="{warn: !info.building_id}">项目</span>
        </cell>
        <cell value-align="left">
          <span slot="title" :class="{warn: !building.developer}">委托方</span>
          <span v-if="building.developer">{{building.developer}}</span>
          <span v-if="!building.developer" style="color:red">项目缺少开发商信息</span>
        </cell>
        <cell @click.native="selectCompany" :is-link="companyPickerData.length != 1" 
          :value="companyText" value-align="left">
          <span slot="title" :class="{warn: !info.company_id}">代理方</span>
        </cell>
        <cell value-align="left" :value="building.customer"
          :link="{name: 'CustomerView', params: { id: info.customer_id }}">
          <span slot="title" :class="{warn: !info.customer_id}">客户</span>
        </cell>
        <x-input ref="inpConfirmAcreage" title="面积" type="number" :required="true" v-model="info.acreage" :max="10" :show-clear="false"
          @on-click-error-icon="acreageError" :should-toast-error="false" @on-change="validateForm">
          <span slot="right">平方米</span>
        </x-input>
        <cell :value="info.rent_sell" value-align="left" :is-link="true" @click.native="showRentSellPicker = true">
          <span slot="title" :class="{warn: !isRentSellValid}">租售</span>
        </cell>
        <cell :value="info.confirm_date" value-align="left" :is-link="true" @click.native="selectConfirmDate">
          <span slot="title" :class="{warn: !isConfirmDateValid}">确认日期</span>
        </cell>
        <cell title="有效期" value-align="left">
          <inline-x-number style="float:left;margin:0 5px 0 0;" width="80px" :min="1" :max="12" :step="1" v-model="info.period"></inline-x-number>
          <div style="float:left;display:inline-block;line-height:28px;">个月</div>
        </cell>
      </group>

      <group gutter="10px">
        <x-textarea placeholder="备注" :rows="3" v-model="info.rem" :max="200"></x-textarea>
      </group>

      <actionsheet v-model="showCompanyPicker" :menus="companyPickerData" theme="android" @on-click-menu="companySelect"></actionsheet>
      <actionsheet v-model="showRentSellPicker" :menus="rentSellPickerData" theme="android" @on-click-menu="rentSellSelect"></actionsheet>

      <div class="bottom-bar">
        <x-button type="primary" class="bottom-btn" @click.native="save" :disabled="!formValidate">
          <x-icon type="android-archive" class="btn-icon"></x-icon> 保存
        </x-button>
      </div>
  </div>
</template>

<script>
import rentSellData from '../../data/rent_sell.json'

export default {
  components: {
  },
  data () {
    return {
      id: 0,
      isRentSellValid: true,
      isConfirmDateValid: true,
      formValidate: false,
      building: {
        building: '',
        developer: '',
        customer: ''
      },
      info: {
        __token__: '',
        building_id: 0, // 项目
        customer_id: 0,
        acreage: null, // 面积
        rent_sell: '', // 租售
        confirm_date: '', // 确认日期
        period: 3, // 有效期（月)
        rem: '', // 备注
        company_id: 0
      },
      showCompanyPicker: false,
      companyPickerData: [],
      companyText: '',
      showRentSellPicker: false,
      rentSellPickerData: []
    }
  },
  beforeRouteEnter (to, from, next) {
    next(vm => {
      if (vm.rentSellPickerData.length === 0) {
        vm.rentSellPickerData.push(rentSellData[1])
        vm.rentSellPickerData.push(rentSellData[2])
      }

      if (to.params.id) {
        vm.id = parseInt(to.params.id)
        if (isNaN(vm.id)) {
          vm.id = 0
        }
      }

      if (to.params.bid) {
        vm.info.building_id = parseInt(to.params.bid)
        if (isNaN(vm.info.building_id)) {
          vm.info.building_id = 0
        }
      }

      if (to.params.cid) {
        vm.info.customer_id = parseInt(to.params.cid)
        if (isNaN(vm.info.customer_id)) {
          vm.info.customer_id = 0
        }
      }

      vm.$get('/api/confirm/edit?id=' + vm.id + '&bid=' + vm.info.building_id + '&cid=' + vm.info.customer_id, res => {
        if (res.success) {
          if (res.data.companyList) {
            for (let i in res.data.companyList) {
              vm.companyPickerData.push({
                label: res.data.companyList[i].title,
                value: res.data.companyList[i].id
              })
              if (res.data.companyList[i].default === 1) {
                vm.info.company_id = res.data.companyList[i].id
                vm.companyText = res.data.companyList[i].title
              }
            }
          }
          for (let item in vm.building) {
            if (res.data[item] !== undefined && res.data[item] !== null) {
              vm.building[item] = res.data[item]
            }
          }
          for (let item in vm.info) {
            if (res.data[item] !== undefined && res.data[item] !== null) {
              if (item === 'company_id') {
                if (res.data.company_id > 0) {
                  vm.info.company_id = res.data.company_id
                  for (let i in vm.companyPickerData) {
                    if (
                      vm.info.company_id === vm.companyPickerData[i].value
                    ) {
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

          if (vm.info.confirm_date) {
            vm.info.confirm_date = vm.$dateFormat(
              new Date(Date.parse(vm.info.confirm_date.replace(/-/g, '/'))),
              'YYYY-MM-DD'
            )
          }
          if (!vm.info.acreage || vm.info.acreage === '0') {
            vm.info.acreage = null
          }

          if (vm.info.company_id === 0 && vm.companyPickerData.length) {
            vm.info.company_id = vm.companyPickerData[0].value
            vm.companyText = vm.companyPickerData[0].label
          }

          if (vm.id) {
            vm.$emit('on-view-loaded', '修改客户确认书')
          } else {
            vm.$emit('on-view-loaded', '添加客户确认书')
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
    acreageError () {
      this.$vux.toast.show({
        text: '请输入面积'
      })
    },
    validateForm () {
      this.formValidate = this.info.building_id &&
        this.building.developer &&
        this.info.company_id &&
        this.info.customer_id &&
        this.$refs.inpConfirmAcreage.valid &&
        this.info.rent_sell &&
        this.vm.isConfirmDateValid
    },
    rentSellSelect (key, item) {
      this.info.rent_sell = item.value
      this.isRentSellValid = this.info.rent_sell.length > 0
      this.validateForm()
    },
    selectConfirmDate () {
      let vm = this
      vm.$vux.datetime.show({
        value: vm.info.confirm_date,
        cancelText: '取消',
        confirmText: '确定',
        onHide () {
          vm.isConfirmDateValid = vm.info.confirm_date.length > 0
          vm.validateForm()
        },
        onConfirm (val) {
          vm.info.confirm_date = val
          vm.isConfirmDateValid = vm.info.confirm_date.length > 0
          vm.validateForm()
        }
      })
    },
    companySelect (key, item) {
      this.info.company_id = item.value
      this.companyText = item.label
      this.validateForm()
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
            vm.$router.push({ name: 'Company' })
          }
        })
      }
    },
    save () {
      this.isRentSellValid = this.info.rent_sell.length > 0
      this.isConfirmDateValid = this.info.confirm_date.length > 0
      this.validateForm()
      if (!this.formValidate) {
        return
      }
      this.$vux.loading.show()
      this.info.share = this.info.share ? 1 : 0
      this.$post('/api/confirm/edit?id=' + this.id, this.info, res => {
        this.$vux.loading.hide()
        if (res.success) {
          if (this.id === 0) {
            this.id = res.data
            this.$router.push({name: 'ConfirmView', params: {id: this.id, bid: 0, cid: 0}})
          } else {
            this.$router.back()
          }
        } else {
          this.info.__token__ = res.data
          this.$vux.toast.show({
            text: res.message,
            width: '16em'
          })
        }
      })
    }
  },
  computed: {}
}
</script>

<style lang="less">
.warn {
  color: red;
}
</style>