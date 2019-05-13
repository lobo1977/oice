<template>
  <div>
    <tab v-show="id > 0">
      <tab-item selected @on-item-click="tab = 0">基本信息</tab-item>
      <tab-item @on-item-click="tab = 1" :disabled="id===0">图片</tab-item>
    </tab>

    <div v-transfer-dom>
      <previewer :list="images" ref="prevUnitEdit" :options="previewOptions">
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

    <div v-if="tab === 0">
      <group gutter="0" label-width="4em" label-margin-right="1em" label-align="right">
        <x-input title="楼栋" v-model="info.building_no" :max="10"></x-input>
        <x-input title="楼层" type="tel" :max="3" v-model="info.floor" 
          :show-clear="false" placeholder="地下填写负数">
          <span slot="right">层</span>
        </x-input>
        <x-input ref="inpRoom" title="房间号" v-model="info.room" :required="true" :max="10"
          placeholder="填写房间号或整层"
          @on-click-error-icon="roomError" :should-toast-error="false" @on-change="validateForm"></x-input>
        <cell title="朝向">
          <checker v-model="selectedFace" type="checkbox" @on-change="selectFace"
            default-item-class="face-item" selected-item-class="face-item-selected">
            <checker-item v-for="i in faceSelectData" :key="i" :value="i">{{i}}</checker-item>
          </checker>
        </cell>
        <x-input title="面积" type="tel" :max="5" v-model="info.acreage" :show-clear="false">
          <span slot="right">平方米</span>
        </x-input>
        <cell title="租售" @click.native="showRentSellPicker = true" :is-link="true" :value="info.rent_sell" value-align="left"></cell>
        <x-input title="出租价格" type="tel" :max="4" v-model="info.rent_price" :show-clear="false">
          <span slot="right">元/平方米/日</span>
        </x-input>
        <x-input title="出售价格" type="tel" :max="8" v-model="info.sell_price" :show-clear="false">
          <span slot="right">元/平方米</span>
        </x-input>
        <cell title="装修状况" @click.native="showDecorationSelect = true" is-link :value="info.decoration" value-align="left"></cell>
        <cell title="状态" @click.native="showStatusPicker = true" :is-link="true" :value="statusText" value-align="left"></cell>
        <cell title="到期日" :value="info.end_date" value-align="left" :is-link="true" @click.native="selectEndDate"></cell>
      </group>

      <group gutter="10px">
        <x-textarea placeholder="备注" :rows="3" v-model="info.rem" :max="200"></x-textarea>
      </group>

      <group gutter="10px" label-width="4em" label-margin-right="1em" label-align="right">
        <x-input v-if="id === 0" ref="inpUnitLinkman" title="联系人" v-model="info.linkman" :max="30"></x-input>
        <x-input v-if="id === 0" ref="inpUnitMobile" title="联系电话" placeholder="请输入手机号码" 
          type="tel" v-model="info.mobile" :max="11" is-type="china-mobile"
          @on-change="validateForm" @on-click-error-icon="mobileError" :should-toast-error="false"></x-input>
      </group>

      <group v-if="info.user_id == user.id" gutter="10px" label-width="4em" label-margin-right="1em" label-align="right">
        <cell title="所属企业" @click.native="selectCompany" :is-link="companyPickerData.length != 1" 
            :value="companyText" value-align="left"></cell>
        <x-switch title="是否公开" inline-desc="是否全网可见" v-model="info.share"></x-switch>
      </group>

      <popup v-model="showDecorationSelect">
        <popup-header
          left-text="取消"
          right-text="完成"
          @on-click-left="showDecorationSelect = false"
          @on-click-right="confirmSelectDecoration"></popup-header>
        <checker v-model="selectedDecoration" class="decoration-checker-box"
          type="checkbox"
          default-item-class="checker-item"
          selected-item-class="checker-item-selected">
          <checker-item v-for="i in decorationSelectData" :key="i" :value="i">{{i}}</checker-item>
        </checker>
      </popup>

      <actionsheet v-model="showRentSellPicker" :menus="rentSellPickerData" theme="android" @on-click-menu="rentSellSelect"></actionsheet>
      <actionsheet v-model="showStatusPicker" :menus="statusPickerData" theme="android" @on-click-menu="statusSelect"></actionsheet>
      <actionsheet v-model="showCompanyPicker" :menus="companyPickerData" theme="android" @on-click-menu="companySelect"></actionsheet>

      <div class="bottom-bar">
        <x-button type="primary" class="bottom-btn" @click.native="save" :disabled="!formValidate">
          <x-icon type="android-archive" class="btn-icon"></x-icon> 保存
        </x-button>
      </div>
    </div>

    <div v-if="tab === 1">
      <flexbox :gutter="0" wrap="wrap">
        <flexbox-item :span="1/4" v-for="(item, index) in images" :key="index">
          <img :src="item.msrc" @click="preview(index)" class="building-img">
        </flexbox-item>
      </flexbox>
      <div class="bottom-bar">
        <form ref="frmUploadUnitImage">
          <x-button type="warn" class="bottom-btn">上传</x-button>
          <input type="hidden" name="id" :value="id">
          <input type="file" class="upload" name="images[]" multiple="multiple"
            accept="image/*" @change="upload"/>
        </form>
      </div>
    </div>

  </div>
</template>

<script>
import { Previewer, TransferDom, PopupHeader, Checker, CheckerItem } from 'vux'
import faceData from '../../data/face.json'
import rentSellData from '../../data/rent_sell.json'
import decorationData from '../../data/decoration.json'
import statusData from '../../data/unit_status.json'

export default {
  directives: {
    TransferDom
  },
  components: {
    Previewer,
    PopupHeader,
    Checker,
    CheckerItem
  },
  data () {
    return {
      user: {
        id: 0
      },
      tab: 0,
      id: 0,
      formValidate: false,
      info: {
        __token__: '',
        building_id: 0,
        building_no: '',      // 楼栋
        floor: null,          // 楼层
        room: '',             // 房间号
        face: '',             // 朝向
        acreage: null,        // 面积
        rent_sell: '',        // 租售
        rent_price: null,     // 出租价格
        sell_price: null,     // 出售价格
        decoration: '',       // 装修状况
        status: 1,            // 状态(默认空置)
        end_date: '',         // 到日期
        rem: '',              // 备注
        user_id: 0,
        company_id: 0,        // 所属企业
        share: true,          // 是否公开
        linkman: '',          // 联系人
        mobile: ''            // 联系电话
      },
      images: [],
      previewOptions: {
        isClickableElement: function (el) {
          return /previewer-icon/.test(el.className)
        }
      },
      faceSelectData: [],
      selectedFace: [],
      showRentSellPicker: false,
      rentSellPickerData: rentSellData,
      showDecorationSelect: false,
      decorationSelectData: [],
      selectedDecoration: [],
      showStatusPicker: false,
      statusPickerData: statusData,
      statusText: '',
      showCompanyPicker: false,
      companyPickerData: [],
      companyText: ''
    }
  },
  beforeRouteEnter (to, from, next) {
    next(vm => {
      vm.user = vm.$store.state.oice.user || vm.user

      for (let i in faceData) {
        vm.faceSelectData.push(faceData[i].value)
      }
      for (let i in decorationData) {
        vm.decorationSelectData.push(decorationData[i].value)
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

      vm.$get('/api/unit/edit?id=' + vm.id, (res) => {
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
            if (vm.info.face) {
              vm.selectedFace = vm.info.face.split(',')
            }
            if (vm.info.decoration) {
              vm.selectedDecoration = vm.info.decoration.split(',')
            }
            if (vm.info.end_date) {
              vm.info.end_date = vm.$dateFormat(new Date(Date.parse(vm.info.end_date.replace(/-/g, '/'))), 'YYYY-MM-DD')
            }
            if (vm.info.floor === 0 || vm.info.floor === '0') {
              vm.info.floor = null
            }
            if (!vm.info.acreage || vm.info.acreage === '0') {
              vm.info.acreage = null
            }
            if (!vm.info.rent_price || vm.info.rent_price === '0') {
              vm.info.rent_price = null
            }
            if (!vm.info.sell_price || vm.info.sell_price === '0') {
              vm.info.sell_price = null
            }
            if (res.data.images) {
              vm.images = res.data.images
            }
            vm.statusText = vm.statusPickerData[vm.info.status].label
            vm.info.share = vm.info.share === 1
            vm.$emit('on-view-loaded', '修改单元')
          } else {
            vm.info.__token__ = res.data.__token__
            vm.statusText = vm.statusPickerData[vm.info.status].label
            vm.$emit('on-view-loaded', '添加单元')
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
    roomError () {
      this.$vux.toast.show({
        text: '请输入房间号'
      })
    },
    mobileError () {
      this.$vux.toast.show({
        text: this.info.mobile.length ? '联系人手机号码无效' : '请输入联系电话'
      })
    },
    validateForm () {
      if (this.id === 0) {
        this.formValidate = this.$refs.inpRoom.valid &&
          this.$refs.inpUnitLinkman.valid && this.$refs.inpUnitMobile.valid
      } else {
        this.formValidate = this.$refs.inpRoom.valid
      }
    },
    selectFace () {
      this.info.face = this.selectedFace.join()
    },
    rentSellSelect (key, item) {
      this.info.rent_sell = item.value
    },
    confirmSelectDecoration () {
      this.info.decoration = this.selectedDecoration.join()
      this.showDecorationSelect = false
    },
    statusSelect (key, item) {
      this.info.status = item.value
      this.statusText = item.label
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
      this.validateForm()
      if (!this.formValidate) {
        return
      }
      this.$vux.loading.show()
      this.info.share = (this.info.share ? 1 : 0)
      this.$post('/api/unit/edit?id=' + this.id, this.info, (res) => {
        this.$vux.loading.hide()
        if (res.success) {
          if (this.id === 0) {
            this.id = res.data
          }
          this.$router.back()
        } else {
          this.info.__token__ = res.data
          this.$vux.toast.show({
            text: res.message,
            width: '16em'
          })
        }
      })
    },
    preview (index) {
      this.$refs.prevUnitEdit.show(index)
    },
    upload () {
      let form = this.$refs.frmUploadUnitImage
      this.$vux.loading.show()
      this.$postFile('/api/building/uploadUnitImage', form, (res) => {
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
      let index = this.$refs.prevUnitEdit.getCurrentIndex()
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
      let index = this.$refs.prevUnitEdit.getCurrentIndex()
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
  },
  computed: {
  }
}
</script>

<style lang="less">
.pswp__caption__center {
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
  text-align:center !important; 
 }

.building-img { width:100%; height: auto; }

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

.face-item {
  margin-left:5px;
  border: 1px solid #ececec;
  padding: 10px 15px;
  line-height: 1em;
}

.face-item-selected {
  color:#fff;
  background-color: green;
}

.decoration-checker-box {
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