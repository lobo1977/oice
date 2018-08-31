<template>
  <div>
    <form ref="frmCompany">
      <input type="hidden" name="__token__" :value="info.__token__">
      <input type="hidden" name="area" :value="info.area">
      <input type="hidden" name="status" :value="info.status ? 1 : 0">
      <input type="hidden" name="join_way" :value="info.join_way">
      <group gutter="0" label-width="4em" label-margin-right="1em" label-align="right">
        <cell title="Logo">
          <div solt="default" style="height:60px;line-height:0;">
            <img v-show="logoSrc != null && logoSrc != ''" :src="logoSrc" style="height:60px;">
          </div>
          <input id="inputLogo" type="file" name="logo" class="upload" @change="loadLogo"
              accept="image/*">
        </cell>
        <x-input name="title" ref="input_name" title="企业简称" v-model="info.title" :required="true" :max="10"
          @on-click-error-icon="nameError" :should-toast-error="false" @on-change="validateForm"></x-input>
        <x-input name="full_name" title="企业全称" v-model="info.full_name" :max="50"></x-input>
        <popup-picker title="所在地" :data="districtPickerList" @on-change="districtChange"
          :columns="2" :fixed-columns="1" v-model="districtValue" value-text-align="left"></popup-picker>
        <x-input name="address" title="详细地址" v-model="info.address" :max="100"></x-input>
      </group>

      <group gutter="10px">
        <x-textarea name="rem" placeholder="企业介绍" :rows="5" v-model="info.rem" :max="500"></x-textarea>
      </group>

      <group gutter="10px">
        <x-switch title="启用公章" inline-desc="在生成的确认书中自动添加公章" v-model="info.enable_stamp"></x-switch>
        <cell title="公章" v-show="info.enable_stamp">
          <div solt="default" style="height:60px;line-height:0;">
            <img v-show="stampSrc != null && stampSrc != ''" :src="stampSrc" style="height:60px;">
          </div>
          <input id="inputStamp" type="file" name="stamp" class="upload" @change="loadStamp"
            accept="image/*">
        </cell>
      </group>

      <group gutter="10px" label-width="4em" label-margin-right="1em" label-align="right">
        <cell title="加入方式" @click.native="showJoinWayPicker = true" :is-link="true" :value="joinWayText" value-align="left"></cell>
        <x-switch title="是否公开" inline-desc="公开企业可以被其他用户检索并加入" v-model="info.status"></x-switch>
      </group>
    </form>

    <actionsheet v-model="showJoinWayPicker" :menus="joinWayPickerList" theme="android" @on-click-menu="selectJoinWay"></actionsheet>
    
    <div class="bottom-bar">
      <x-button type="primary" class="bottom-btn" @click.native="save" :disabled="!formValidate">
        <x-icon type="android-archive" class="btn-icon"></x-icon> 保存
      </x-button>
    </div>
  </div>
</template>

<script>
import { mapActions } from 'vuex'
import districtData from '../../data/beijing_area.json'
import joinWayData from '../../data/join_way.json'

export default {
  components: {
  },
  data () {
    return {
      id: 0,
      formValidate: false,
      info: {
        __token__: '',
        id: 0,
        title: '',     // 简称
        full_name: '', // 全称
        area: '',      // 城区
        address: '',   // 地址
        rem: '',
        join_way: 0,
        status: false,
        enable_stamp: true
      },
      logoSrc: null,
      stampSrc: null,
      districtValue: ['', ''],
      districtPickerList: [],
      showJoinWayPicker: false,
      joinWayText: '',
      joinWayPickerList: joinWayData
    }
  },
  beforeRouteEnter (to, from, next) {
    next(vm => {
      for (let i in districtData) {
        if (!districtData[i].parent && districtData[i].value !== 'all') {
          vm.districtPickerList.push(districtData[i])
        }
      }

      if (to.params.id) {
        vm.id = parseInt(to.params.id)
        if (isNaN(vm.id)) {
          vm.id = 0
        }
      }

      vm.$get('/api/company/edit?id=' + vm.id, (res) => {
        if (res.success) {
          if (vm.id) {
            for (let item in vm.info) {
              if (res.data[item] !== undefined && res.data[item] !== null) {
                vm.info[item] = res.data[item]
              }
            }
            if (res.data.logo) {
              vm.logoSrc = res.data.logo
            }
            if (res.data.stamp) {
              vm.stampSrc = res.data.stamp
            }
            if (vm.info.area) {
              vm.districtValue = [vm.info.area, '']
            }
            vm.joinWayText = vm.joinWayPickerList[vm.info.join_way].label
            vm.info.status = vm.info.status === 1
            vm.info.enable_stamp = vm.info.enable_stamp === 1
            vm.$emit('on-view-loaded', vm.info.title)
          } else {
            vm.info.__token__ = res.data.__token__
            vm.joinWayText = vm.joinWayPickerList[vm.info.join_way].label
          }
        } else {
          vm.$vux.toast.show({
            text: res.message,
            width: '15em'
          })
        }
      })
    })
  },
  methods: {
    ...mapActions([
      'getUser'
    ]),
    nameError () {
      this.$vux.toast.show({
        text: '请输入企业名称'
      })
    },
    validateForm () {
      this.formValidate = this.$refs.input_name.valid
    },
    districtChange (val) {
      this.info.area = (val[0] === 'all') ? '' : val[0]
    },
    loadLogo () {
      let src = document.getElementById('inputLogo')
      if (!src.files || !src.files[0]) {
        return
      }
      let reader = new FileReader()
      reader.onload = (e) => {
        this.logoSrc = e.target.result
      }
      reader.readAsDataURL(src.files[0])
    },
    loadStamp () {
      let src = document.getElementById('inputStamp')
      if (!src.files || !src.files[0]) {
        return
      }
      let reader = new FileReader()
      reader.onload = (e) => {
        this.stampSrc = e.target.result
      }
      reader.readAsDataURL(src.files[0])
    },
    selectJoinWay (key, item) {
      this.info.join_way = item.value
      this.joinWayText = item.label
    },
    save () {
      this.validateForm()
      if (!this.formValidate) {
        return
      }
      let form = this.$refs.frmCompany
      this.$vux.loading.show()
      this.info.status = (this.info.status ? 1 : 0)
      this.info.enable_stamp = (this.info.enable_stamp ? 1 : 0)
      this.$postFile('/api/company/edit?id=' + this.id, form, (res) => {
        if (res.success) {
          this.$updateUser((res2) => {
            this.getUser()
            this.$vux.loading.hide()
            this.$router.back()
          })
        } else {
          this.$vux.loading.hide()
          if (res.data) {
            this.info.__token__ = res.data
          }
          this.$vux.toast.show({
            text: res.message,
            width: '15em'
          })
        }
      })
    }
  }
}
</script>

<style lang="less">
.upload {
    position:absolute;
    top:0;
    left:0;
    width:100%;
    height: 100%;
    opacity:0;
  }
</style>