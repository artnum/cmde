/* Swiss BVR checksum and, added bonus, length of id in front with a letter.
 * This design is to help live check (check as user type reference), the letter
 * indicate when we can checksum and green light for search
 */
function RID() {
    this.table = [
        [0, 9, 4, 6, 8, 2, 7, 1, 3, 5],
        [9, 4, 6, 8, 2, 7, 1, 3, 5, 0],
        [4, 6, 8, 2, 7, 1, 3, 5, 0, 9],
        [6, 8, 2, 7, 1, 3, 5, 0, 9, 4],
        [8, 2, 7, 1, 3, 5, 0, 9, 4, 6],
        [2, 7, 1, 3, 5, 0, 9, 4, 6, 8],
        [7, 1, 3, 5, 0, 9, 4, 6, 8, 2],
        [1, 3, 5, 0, 9, 4, 6, 8, 2, 7],
        [3, 5, 0, 9, 4, 6, 8, 2, 7, 1],
        [5, 0, 9, 4, 6, 8, 2, 7, 1, 3]
    ]
}

RID.prototype.key = function (uid) {
    let r = 0;
    let ref = String(uid).split('').reverse().join('')
    for (let i = ref.length - 1; i >= 0; i--) {
        r = this.table[r][parseInt(ref.charAt(i))];
    }

    return [0, 9, 8, 7, 6, 5, 4, 3, 2, 1][r]
}

RID.prototype.generate = function (uid) {
    return `RIG-${String(String(uid).length - 1).padStart(2, '0')}${String(uid)}${this.key(uid)}`
}

RID.prototype.check = function (reference) {
    const str = String(reference).split('-')[1]
    const k = str.substring(str.length - 1)
    return parseInt(k) === this.key(str.substring(1, str.length - 2))
}