// Comment System - Local Storage Implementation
window.commentSystem = {
    _data: {},
    
    addComment: function(houseId, name, comment) {
        if (!this._data[houseId]) {
            this._data[houseId] = [];
        }
        this._data[houseId].push({
            id: Date.now(),
            name: name,
            comment: comment,
            visible: true,
            timestamp: Date.now()
        });
        // Sync ke localStorage
        this.saveToLocalStorage();
    },
    
    getComments: function(houseId) {
        return this._data[houseId] || [];
    },
    
    getCommentCount: function(houseId) {
        return (this._data[houseId] || []).filter(c => c.visible).length;
    },
    
    toggleCommentVisibility: function(houseId, commentId) {
        let arr = this._data[houseId];
        if (!arr) return;
        let comment = arr.find(c => c.id === commentId);
        if (comment) {
            comment.visible = !comment.visible;
            this.saveToLocalStorage();
        }
    },
    
    deleteComment: function(houseId, commentId) {
        if (!this._data[houseId]) return;
        this._data[houseId] = this._data[houseId].filter(c => c.id !== commentId);
        this.saveToLocalStorage();
    },
    
    saveToLocalStorage: function() {
        if (window.localStorage) {
            localStorage.setItem('comments', JSON.stringify(this._data));
        }
    },
    
    formatTimestamp: function(ts) {
        const d = new Date(ts);
        return d.toLocaleDateString('id-ID') + ' ' + d.toLocaleTimeString('id-ID', { 
            hour: '2-digit', 
            minute: '2-digit' 
        });
    }
};

// Inisialisasi data dari localStorage saat halaman dimuat
if (window.localStorage && localStorage.getItem('comments')) {
    try {
        window.commentSystem._data = JSON.parse(localStorage.getItem('comments'));
    } catch(e) {
        window.commentSystem._data = {};
    }
}
