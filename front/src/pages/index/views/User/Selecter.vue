<template>
  <div style="height:100%">
    <x-header class="fix-top"
      :left-options="leftOptions" title="选择用户">
      <span slot="overwrite-left">
        <x-icon type="close" size="20" class="icon-close" @click="close"></x-icon>
      </span>
      <span slot="right" v-if="multiple" @click="confirm" 
        :class="{enable: checkCount > 0}">
        确定
      </span>
    </x-header>

    <search ref="searchUser" @on-change="getList"
      :results="list" :auto-fixed="false" style="position:fixed;top:46px;"></search>
    
    <group :gutter="0" style="margin-top:92px;">
      <cell v-for="(item, index) in list" :key="index" :title="item.title" 
        :inline-desc="item.mobile" @click.native="select(item)">
        <img slot="icon" :src="item.avatar" class="cell-image">
        <div v-if="multiple" solt="default">
          <check-icon :value.sync="item.checked" @update:value="check"></check-icon>
        </div>
      </cell>
    </group>
  </div>
</template>

<script>
import { XHeader, Search, CheckIcon } from 'vux'

export default {
  name: 'user-selecter',
  components: {
    XHeader,
    Search,
    CheckIcon
  },
  props: {
    isShown: {
      type: Boolean,
      default: false
    },
    multiple: {
      type: Boolean,
      default: false
    },
    exclude: [],
    company: 0
  },
  data () {
    return {
      leftOptions: {
        showBack: false
      },
      isSearching: false,
      list: [],
      checkCount: 0,
      selected: []
    }
  },
  mounted () {
  },
  methods: {
    getList (val) {
      this.isSearching = true
      this.$post('/api/user/search', {
        company: this.company,
        keyword: (val !== undefined && val != null ? val : '')
      }, (res) => {
        this.isSearching = false
        if (res.success) {
          this.list = res.data
        } else {
          this.list = []
        }
      })
    },
    select (item) {
      if (this.multiple) {
        item.checked = !item.checked
        this.check(item.checked)
      } else {
        this.selected = [item]
        this.confirm()
      }
    },
    check (checked) {
      if (checked) this.checkCount++
      else this.checkCount--
    },
    confirm () {
      if (this.multiple) {
        this.selected = []
        for (let i in this.list) {
          if (this.list[i].checked) {
            this.selected.push(this.list[i])
          }
        }
      }
      if (this.selected.length) {
        if (this.multiple) {
          this.$emit('on-confirm', this.selected)
        } else {
          this.$emit('on-confirm', this.selected[0])
        }
        this.close()
      }
    },
    close () {
      this.$emit('on-close')
    }
  },
  watch: {
    isShown (val) {
      if (val) {
        this.selected = []
        this.checkCount = 0
        this.getList()
      }
    }
  }
}
</script>

<style>
.icon-close {
  position:relative;
  fill:#ccc;
}

.fix-top {
  position:fixed;
  top:0;
  z-index:100;
  width:100%;
}

.enable {
  color:#fff;
}
</style>
