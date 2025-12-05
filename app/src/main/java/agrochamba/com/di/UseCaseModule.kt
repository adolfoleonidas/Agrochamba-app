package agrochamba.com.di

import agrochamba.com.domain.repository.UserRepository
import agrochamba.com.domain.usecase.auth.LoginUseCase
import agrochamba.com.domain.usecase.auth.RegisterCompanyUseCase
import agrochamba.com.domain.usecase.auth.RegisterUserUseCase
import agrochamba.com.domain.usecase.auth.SendPasswordResetUseCase
import dagger.Module
import dagger.Provides
import dagger.hilt.InstallIn
import dagger.hilt.components.SingletonComponent
import javax.inject.Singleton

/**
 * Módulo de Hilt para proporcionar casos de uso.
 */
@Module
@InstallIn(SingletonComponent::class)
object UseCaseModule {

    @Provides
    @Singleton
    fun provideLoginUseCase(userRepository: UserRepository): LoginUseCase {
        return LoginUseCase(userRepository)
    }

    @Provides
    @Singleton
    fun provideRegisterUserUseCase(
        userRepository: UserRepository
    ): RegisterUserUseCase {
        return RegisterUserUseCase(userRepository)
    }

    @Provides
    @Singleton
    fun provideRegisterCompanyUseCase(
        userRepository: UserRepository
    ): RegisterCompanyUseCase {
        return RegisterCompanyUseCase(userRepository)
    }

    @Provides
    @Singleton
    fun provideSendPasswordResetUseCase(userRepository: UserRepository): SendPasswordResetUseCase {
        return SendPasswordResetUseCase(userRepository)
    }

}

