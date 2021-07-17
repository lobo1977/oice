<template>
  <div>
    <form ref="frmArticle">
      <input type="hidden" name="__token__" :value="info.__token__">
      <input type="hidden" name="top" :value="bool_top ? 1 : 0">
      <input type="hidden" name="status" :value="bool_status ? 1 : 0">
      <input type="hidden" name="type" :value="info.type">
      <input type="hidden" name="content" :value="info.content">
      <group gutter="0" label-width="4em" label-margin-right="1em" label-align="right">
        <cell title="文章类别" @click.native="showTypePicker = true" :is-link="true" :value="typeText" value-align="left"></cell>
        <x-input ref="inpTitle" name="title" title="文章标题" v-model="info.title" :required="true" :max="30"
          @on-click-error-icon="titleError" :should-toast-error="false" @on-change="validateForm"></x-input>
        <cell title="封面图">
          <div solt="default" style="height:80px;line-height:0;">
            <img v-show="coverSrc != null && coverSrc != ''" :src="coverSrc" style="height:80px;">
          </div>
          <input id="inputCover" type="file" name="cover" class="upload" @change="loadCover" accept="image/*">
        </cell>
      </group>

      <group gutter="10px">
        <x-textarea name="summary" placeholder="文章摘要" :rows="3" v-model="info.summary" :max="200"></x-textarea>
      </group>

      <group gutter="10px">
        <quill-editor v-model="info.content"
          ref="contentEditor"
          :options="editorOption"
          @blur="onEditorBlur($event)"
          @focus="onEditorFocus($event)"
          @ready="onEditorReady($event)">
        </quill-editor>
      </group>

      <group gutter="10px">
        <x-switch title="是否顶置" inline-desc="文章置顶展示" v-model="bool_top"></x-switch>
        <x-switch title="是否公开" inline-desc="文章可以被其他人浏览" v-model="bool_status"></x-switch>
      </group>
    </form>

    <form ref="frmUpload" style="display:none">
      <input id="inputImage" type="file" name="image" @change="uploadImage" accept="image/*">
    </form>

    <actionsheet v-model="showTypePicker" :menus="typePickerList" theme="android" @on-click-menu="selectType"></actionsheet>
    
    <div class="bottom-bar">
      <x-button type="primary" class="bottom-btn" @click.native="save" :disabled="!formValidate">
        <x-icon type="android-archive" class="btn-icon"></x-icon> 保存
      </x-button>
    </div>
  </div>
</template>

<script>
import { mapActions } from 'vuex'
// import 'quill/dist/quill.core.css'
import 'quill/dist/quill.snow.css'
// import 'quill/dist/quill.bubble.css'

import { quillEditor } from 'vue-quill-editor'
import articleType from '../../data/article_type.json'

export default {
  components: {
    quillEditor
  },
  data () {
    return {
      id: 0,
      formValidate: false,
      info: {
        __token__: '',
        id: 0,
        type: 0,
        title: '',
        summary: '',
        content: '',
        top: 0,
        status: 1
      },
      bool_top: false,
      bool_status: true,
      coverSrc: null,
      showTypePicker: false,
      typeText: '',
      typePickerList: articleType,
      editor: null,
      editorOption: {
        theme: 'snow',
        placeholder: '文章正文',
        modules: {
          toolbar: {
            container: [
              ['bold', 'italic', 'underline'],
              [{ size: ['small', false, 'large', 'huge'] }],
              [{ color: [] }, { background: [] }],
              [{ align: [] }],
              ['link', 'image', 'video']
            ],
            handlers: {
              image: function (value) {
                if (value) {
                  document.querySelector('#inputImage').click()
                } else {
                  this.editor.format('image', false)
                }
              }
            }
          }
        }
      }
    }
  },
  beforeRouteEnter (to, from, next) {
    next(vm => {
      if (to.params.id && !isNaN(to.params.id)) {
        vm.id = parseInt(to.params.id)
      } else if (to.params.type && !isNaN(to.params.type)) {
        vm.info.type = parseInt(to.params.type)
      }

      vm.$get('/api/article/edit?id=' + vm.id, (res) => {
        if (res.success) {
          if (vm.id) {
            for (let item in vm.info) {
              if (res.data[item] !== undefined && res.data[item] !== null) {
                vm.info[item] = res.data[item]
              }
            }
            if (res.data.cover) {
              vm.coverSrc = res.data.cover
            }
            vm.bool_top = vm.info.top === 1
            vm.bool_status = vm.info.status === 1
            vm.$emit('on-view-loaded', vm.info.title)
          } else {
            vm.info.__token__ = res.data.__token__
          }
          vm.typeText = vm.typePickerList[vm.info.type].label
        } else {
          vm.$vux.toast.show({
            text: res.message,
            width: '15em'
          })
        }
      })
    })
  },
  mounted: function () {
    this.editor = this.$refs.contentEditor.quill
  },
  beforeDestroy: function () {
    this.editor = null
    delete this.editor
  },
  methods: {
    ...mapActions([
      'getUser'
    ]),
    titleError () {
      this.$vux.toast.show({
        text: '请输入文章标题'
      })
    },
    onEditorBlur () {
    },
    onEditorFocus () {
    },
    onEditorReady () {
    },
    validateForm () {
      this.formValidate = this.$refs.inpTitle.valid
    },
    loadCover () {
      let src = document.getElementById('inputCover')
      if (!src.files || !src.files[0]) {
        return
      }
      let reader = new FileReader()
      reader.onload = (e) => {
        this.coverSrc = e.target.result
      }
      reader.readAsDataURL(src.files[0])
    },
    selectType (key, item) {
      this.info.type = item.value
      this.typeText = item.label
    },
    uploadImage () {
      let form = this.$refs.frmUpload
      this.$vux.loading.show()
      this.$postFile('/api/article/upload', form, (res) => {
        if (res.success) {
          this.$vux.loading.hide()
          let selectionLength = this.editor.getSelection().index
          this.editor.insertEmbed(selectionLength, 'image',
            window.location.protocol + '//' + window.location.host + res.data)
          this.editor.setSelection(selectionLength + 1)
        } else {
          this.$vux.loading.hide()
          this.$vux.toast.show({
            text: res.message,
            width: '15em'
          })
        }
      })
    },
    save () {
      this.validateForm()
      if (!this.formValidate) {
        return
      }
      let form = this.$refs.frmArticle
      this.$vux.loading.show()
      this.info.status = (this.info.bool_status ? 1 : 0)
      this.$postFile('/api/article/edit?id=' + this.id, form, (res) => {
        if (res.success) {
          this.$vux.loading.hide()
          this.$router.back()
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
.ql-container {
  height: 350px;
}
</style>