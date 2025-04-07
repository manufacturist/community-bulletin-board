// Post animations
const existingSvg = document.getElementById("pushPin")

document.querySelectorAll(".pinWrapper").forEach((element) => {
    const classes = element.firstElementChild.classList
    element.innerHTML = ""

    const newSvg = existingSvg.cloneNode(true)
    newSvg.removeAttribute("id")
    newSvg.classList = classes

    element.appendChild(newSvg)
})

const resetPostState = function (post) {
    if (!post.classList.contains('is-animating')) {
        post.style.transform = post.style.transform.replace('rotateY(180deg)', '')

        if (post.classList.contains('flip')) {
            post.classList.add('unflip')
            setTimeout(() => post.classList.remove('unflip'), 250)

            post.classList.add('is-animating')
            setTimeout(() => post.classList.remove('is-animating'), 500)
        }

        post.classList.remove('flip')
    }
}

document.addEventListener('click', function (event) {
    document.querySelectorAll('.post').forEach(post => {
        if (post.contains(event.target)) {
            post.classList.add('is-animating')
            setTimeout(() => post.classList.remove('is-animating'), 500)

            if (post.classList.contains('flip')) {
                post.style.transform = post.style.transform.replace('rotateY(180deg)', '')
                post.classList.remove('flip')
                post.classList.add('unflip')
                setTimeout(() => post.classList.remove('unflip'), 250)
            } else {
                post.classList.add('flip')

                if (post.style.transform.includes('rotateY(180deg)')) {
                    post.style.transform = post.style.transform.replace('rotateY(180deg)', '')
                } else {
                    post.style.transform += ' rotateY(180deg)'
                }
            }
        } else {
            resetPostState(post)
        }
    })
})

document.querySelectorAll('.post').forEach(post => {
    post.addEventListener('blur', () => resetPostState(post))
    post.addEventListener('mouseleave', () => resetPostState(post))
})
